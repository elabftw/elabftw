from time import time
from uuid import uuid4

from sqlalchemy import Column, Integer, String  # type: ignore
from sqlalchemy.dialects.postgresql import JSONB  # type: ignore
from werkzeug.security import (  # type: ignore
    check_password_hash,
    generate_password_hash,
)

from .database import Base


class LibraryMeta(Base):
    __tablename__ = "library_metadata"
    __table_args__ = {"schema": "indigoservice"}
    library_id = Column(String(36), primary_key=True)
    user_data = Column(JSONB)
    service_data = Column(JSONB)

    def __init__(self, name=None, user_data=None):
        self.library_id = str(uuid4())
        self.user_data = user_data

        current_timestamp = int(time() * 1000)
        self.service_data = {
            "name": name,
            "created_timestamp": current_timestamp,
            "updated_timestamp": current_timestamp,
            "structures_count": 0,
        }


class User(Base):
    __tablename__ = "users"
    __table_args__ = {"schema": "indigoservice"}
    user_id = Column(Integer, primary_key=True)
    username = Column(String(50))
    email = Column(String(100), unique=True)
    password = Column(String(100))
    foreign_auth_provider = Column(String(10))
    foreign_auth_id = Column(Integer)

    def __init__(self, params):
        self.username = params["username"]
        self.email = params["email"]
        self.foreign_auth_provider = params["foreign_auth_provider"]
        self.foreign_auth_id = params["foreign_auth_id"]
        self.set_password(params["password"])

    def __repr__(self):
        return "<User %r, id=%r>" % (self.username, self.user_id)

    def set_password(self, password):
        self.password = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password, password)
