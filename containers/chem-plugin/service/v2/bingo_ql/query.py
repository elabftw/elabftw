import pyparsing as pp  # type: ignore


class QueryBuilder:
    def __init__(self):
        maybe_fuzzy = pp.Optional(pp.Literal("~")).setResultsName("fuzzy")
        q_str = pp.MatchFirst([pp.QuotedString('"'), pp.QuotedString("'")])
        nq_str = pp.Word(pp.alphanums + "_").setResultsName("no_quotes")
        term = maybe_fuzzy + pp.MatchFirst([q_str, nq_str]).setResultsName(
            "term"
        )
        v_term = pp.MatchFirst([q_str, nq_str]).setResultsName("value_term")
        number = pp.Word(pp.nums + ".").setResultsName("number")

        string_eq = pp.MatchFirst(
            [pp.Literal("!~").setResultsName("fuzzy_neq"), pp.Literal("~")]
        )

        simple_eq = pp.MatchFirst(
            [pp.Literal("="), pp.Literal("!=")]
        ).setResultsName("eq_rel")

        order = pp.MatchFirst(
            [
                pp.Literal("<="),
                pp.Literal(">="),
                pp.Literal("<"),
                pp.Literal(">"),
            ]
        )

        num_relation = (
            pp.MatchFirst([simple_eq, order]).setResultsName("num_rel")
            + number
        )
        str_relation = pp.MatchFirst([simple_eq, string_eq]) + v_term

        relation = pp.MatchFirst([num_relation, str_relation]).setResultsName(
            "value_test"
        )

        prop_test = pp.Group(term + pp.Optional(relation))

        op_or = pp.Keyword("OR", caseless=True)
        op_and = pp.Keyword("AND", caseless=True)
        op_both = pp.MatchFirst([op_or, op_and])
        self.parser = pp.Or(
            [
                prop_test,
                pp.delimitedList(prop_test, delim=op_or).setResultsName(
                    "op_or"
                ),
                pp.delimitedList(prop_test, delim=op_and).setResultsName(
                    "op_and"
                ),
                pp.delimitedList(prop_test, delim=op_both).setResultsName(
                    "op_both"
                ),
            ]
        )

    def parse_query(self, query_string):
        return self.parser.parseString(query_string, parseAll=True)

    def build_query_for_term(self, results, idx, term_is_prop_name):
        term = results.get("term")
        if results.get("fuzzy") or results.get("no_quotes"):
            query = "elems->>'{}' LIKE %(property_term_{})s".format(
                "x" if term_is_prop_name else "y", idx
            )
            bind_name = "%{0}%".format(term.lower())
        else:
            query = "elems->>'{}' = %(property_term_{})s".format(
                "x" if term_is_prop_name else "y", idx
            )
            bind_name = term.lower()

        self.bind_params["property_term_" + idx] = bind_name

        return query

    def build_query_for_value(self, results, idx):
        if results.get("number"):
            query = " ".join(
                [
                    "jsonb_typeof(elems->'y') = 'number' AND (elems->>'y')::float",
                    results.get("num_rel"),
                    "%(property_value_{0})s".format(idx),
                ]
            )
            bind_name = results.get("number")
        elif results.get("eq_rel"):
            query = "elems->>'y' {0} %(property_value_{1})s".format(
                results.get("eq_rel"), idx
            )
            bind_name = results.get("value_term").lower()
        else:
            query = "elems->>'y' {0}LIKE %(property_value_{1})s".format(
                "NOT " if results.get("fuzzy_neq") else "", idx
            )
            bind_name = "%{0}%".format(results.get("value_term").lower())
        self.bind_params["property_value_" + idx] = bind_name

        return query

    def build_query(self, query_string):
        queries = []
        self.bind_params = {}
        parse_results = self.parse_query(query_string)

        for i, res in enumerate(parse_results):
            queries.append(self.make_query(res, str(i)))

        if parse_results.get("op_both"):
            raise pp.ParseException("Simultaneous AND/OR is not supported yet")
        elif parse_results.get("op_and"):
            result = queries.pop(0)
            for i, qr in enumerate(queries, start=1):
                subquery = qr.replace("elems", "elems_t{}".format(i))
                result += """)
                    inner join {{1}} t{0} on str.s = t{0}.s\n\
                    inner join jsonb_array_elements(t{0}.p) elems_t{0} on ({1}""".format(
                    i, subquery
                )
        else:
            result = " OR ".join(queries)

        return result

    def make_query(self, parsed_struct, idx):
        if parsed_struct.get("value_test"):
            query = " AND ".join(
                [
                    self.build_query_for_term(parsed_struct, idx, True),
                    self.build_query_for_value(parsed_struct, idx),
                ]
            )
        else:
            query = self.build_query_for_term(parsed_struct, idx, False)
        return "({0})".format(query)
