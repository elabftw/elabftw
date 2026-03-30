import hashlib
import json
import logging

import psycopg2  # type: ignore
import psycopg2.extras  # type: ignore

from ..bingo_ql.query import QueryBuilder
from ..common.util import merge_dicts
from .database import db_session
from .models import LibraryMeta

bingo_logger = logging.getLogger("bingo")


# bingo_logger.addHandler(logging.FileHandler('/srv/api/app.log'))


class BingoPostgresAdapter(object):
    def __init__(self, settings, indigo, indigo_inchi):
        self._indigo = indigo
        self._indigo_inchi = indigo_inchi
        self._builder = QueryBuilder()
        self._settings = settings.get("BINGO_POSTGRES")
        self._connection = None

    @property
    def connection(self):
        if not self._connection:
            self._connection = psycopg2.connect(**self._settings)
        return self._connection

    def _get_structure_sql(self, structure, params):
        stype = params["type"]
        if stype != "sim":
            if stype.lower() == "molformula":
                stype = "gross"
            sql = "m @ (%(structure)s, %(options)s) :: bingo.{0}".format(stype)
            bind = {
                "structure": structure,
                "options": params["options"],
            }
        else:
            sql = "m @ (%(min_sim)s, %(max_sim)s, %(structure)s, %(metric)s) :: bingo.sim"
            bind = {
                "min_sim": params["min_sim"],
                "max_sim": params["max_sim"],
                "structure": structure,
                "metric": params["metric"],
            }
        return sql, bind

    def _get_property_sql(self, input_query):
        query = self._builder.build_query(input_query)
        sql = "({0})".format(query)
        return sql, self._builder.bind_params

    @staticmethod
    def get_table_name_for_id(library_id):
        return "indigoservice.structures_{0}".format(
            library_id.replace("-", "_")
        )

    @staticmethod
    def get_index_name(table_name):
        return "idx_{0}".format(
            hashlib.sha1(table_name.encode("utf-8")).hexdigest()
        )

    # Library

    def library_get_info(self, library_id):
        try:
            cursor = self.connection.cursor()
            cursor.execute(
                "select service_data, user_data, index_data from indigoservice.library_metadata where library_id = %s",
                (library_id,),
            )
            result = cursor.fetchone()
            result_dict = {}
            result_dict["service_data"] = result[0]
            result_dict["user_data"] = result[1]
            props = result[2].get("properties", {}) if result[2] else {}
            result_dict["service_data"]["properties"] = props
            return result_dict
        finally:
            self.connection.commit()

    def library_create(self, library_name, user_data):
        try:
            metalib = LibraryMeta(name=library_name, user_data=user_data)
            db_session.add(metalib)
            db_session.commit()
            cursor = self.connection.cursor()
            cursor.execute(
                "create table {0}(s serial, m text not null, p jsonb not null)".format(
                    self.get_table_name_for_id(metalib.library_id)
                )
            )
            return metalib.library_id
        finally:
            self.connection.commit()

    def library_get_properties(self, library_id):
        try:
            cursor = self.connection.cursor()
            cursor.execute(
                "select distinct elems->>'a' from {0}, jsonb_array_elements(p) elems".format(
                    self.get_table_name_for_id(library_id)
                )
            )
            props = []
            for item in cursor.fetchall():
                props.append(item[0])
            return props
        finally:
            self.connection.commit()

    def library_update(self, library_id, new_data, index_data=None):
        try:
            current_data = self.library_get_info(library_id)
            new_userdata = (
                new_data.pop("user_data") if "user_data" in new_data else None
            )
            service_data = userdata = None
            if new_data:
                service_data = current_data["service_data"]
                service_data.update(new_data)
            if new_userdata:
                userdata = current_data["user_data"]
                userdata.update(new_userdata)
            cursor = self.connection.cursor()
            assignment = ", ".join(
                [
                    x
                    for x in [
                        "user_data = '{}'".format(json.dumps(userdata))
                        if userdata
                        else None,
                        "index_data = '{}'".format(json.dumps(index_data))
                        if index_data
                        else None,
                        "service_data = '{}'".format(json.dumps(service_data))
                        if service_data
                        else None,
                    ]
                    if x is not None
                ]
            )
            cursor.execute(
                "update indigoservice.library_metadata set {} where library_id = '{}'".format(
                    assignment, library_id
                )
            )
            return "OK"
        finally:
            self.connection.commit()

    def library_delete(self, library_id):
        try:
            cursor = self.connection.cursor()
            LibraryMeta.query.filter(
                LibraryMeta.library_id == library_id
            ).delete(synchronize_session=False)
            db_session.commit()
            cursor.execute(
                "drop table {0}".format(self.get_table_name_for_id(library_id))
            )
            return "OK"
        finally:
            self.connection.commit()

    # Library upload

    def library_upload(self, library_id, stream):
        raise NotImplementedError()

    def library_upload_exists(self, library_id, upload_id):
        raise NotImplementedError()

    def library_upload_get_status(self, library_id, upload_id):
        raise NotImplementedError()

    # Search

    def make_full_sql(self, subquery, library_id, q_type, idx):
        table_name = self.get_table_name_for_id(library_id)
        if q_type == "total":
            template = """
                select     s,
                           %(library_id_{{0}})s as library_id
                from       {{1}} struct
                inner join jsonb_array_elements(struct.p) elems
                on         {0}
                group      by s, library_id"""
        elif q_type == "property":
            template = """
                select     struct.s as id,
                           struct.m as data,
                           struct.p as properties,
                           %(library_id_{{0}})s as library_id,
                           init_struct.matched as matched
                from       {{1}} struct
                inner join (
                    select     str.s as id,
                               json_agg(elems->>'a') as matched
                    from       {{1}} str
                    inner join jsonb_array_elements(str.p) elems
                    on         {0}
                    group by   str.s
                ) as init_struct
                on        init_struct.id = struct.s"""
        else:
            template = """
                select s as id,
                       m as data,
                       p as properties,
                       %(library_id_{{0}})s as library_id
                from   {{1}}
                where  {0}"""

        template = template.format(subquery)

        return template.format(idx, table_name), {
            "library_id_" + idx: library_id
        }

    def do_search(self, params):
        try:
            bind_params = {}
            q_text = params["query_text"]
            q_structure = params["query_structure"]
            if q_text:
                prop_sql, bind = self._get_property_sql(q_text)
                bind_params = merge_dicts(bind_params, bind)
            else:
                prop_sql = ""
            if q_structure:
                struct_sql, bind = self._get_structure_sql(q_structure, params)
                bind_params = merge_dicts(bind_params, bind)
            else:
                struct_sql = "true"

            if prop_sql:
                subquery = " AND ".join([prop_sql, struct_sql])
                q_type = "total" if params.get("total", False) else "property"
            else:
                subquery = struct_sql
                q_type = "total" if params.get("total", False) else "structure"

            sqlqueries = []
            for idx, library_id in enumerate(params["library_ids"]):
                query, bind = self.make_full_sql(
                    subquery, library_id, q_type, str(idx)
                )
                bind_params = merge_dicts(bind_params, bind)
                sqlqueries.append(query)
            sql_query = "(" + " UNION ALL ".join(sqlqueries) + ")"
            cursor = self.connection.cursor()
            if params.get("total", False):
                total_query = """
            select library_id, json_agg(s) as id_list
            from {} as combined
            group by library_id""".format(
                    sql_query
                )
                bingo_logger.info(total_query)
                cursor.execute(total_query, bind_params)
                return cursor.fetchall()
            if params.get("limit", None):
                sql_query += " limit %(limit)s"
                bind_params = merge_dicts(
                    bind_params, {"limit": params["limit"]}
                )
            if params.get("offset", None):
                sql_query += " offset %(offset)s"
                bind_params = merge_dicts(
                    bind_params, {"offset": params["offset"]}
                )
            bingo_logger.info(sql_query)
            bingo_logger.info(bind_params)
            cursor = self.connection.cursor()
            cursor.execute(sql_query, bind_params)
            return cursor
        finally:
            self.connection.commit()

    def insert_sdf(self, library_id, data):
        try:
            cursor = self.connection.cursor()
            insert_query = "insert into {0}(m, p) values %s".format(
                self.get_table_name_for_id(library_id)
            )
            psycopg2.extras.execute_values(
                cursor, insert_query, data, template=None, page_size=1000
            )
        finally:
            self.connection.commit()

    def create_indices(self, table_name):
        try:
            index_name = self.get_index_name(table_name)
            cursor = self.connection.cursor()
            cursor.execute(
                "create index if not exists {0} on {1} using bingo_idx (m bingo.molecule) with (IGNORE_STEREOCENTER_ERRORS=1,IGNORE_CISTRANS_ERRORS=1,FP_TAU_SIZE=0)".format(
                    index_name, table_name
                )
            )
            cursor.execute(
                "create index if not exists {0} on {1} (s)".format(
                    "id_" + index_name, table_name
                )
            )
        finally:
            self.connection.commit()

    def drop_indices(self, table_name):
        try:
            index_name = self.get_index_name(table_name)
            cursor = self.connection.cursor()
            cursor.execute("drop index if exists {0}".format(index_name))
            cursor.execute(
                "drop index if exists {0}".format("id_" + index_name)
            )
        finally:
            self.connection.commit()

    def user_all(self):
        try:
            cursor = self.connection.cursor()
            cursor.execute(
                "select user_id, username, email from indigoservice.users"
            )
            result = []
            for item in cursor.fetchall():
                result.append(
                    {
                        "id": item[0],
                        "username": item[1],
                        "email": item[2],
                    }
                )
            return result
        finally:
            self.connection.commit()
