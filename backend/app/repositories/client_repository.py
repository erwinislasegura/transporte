from uuid import UUID
from sqlalchemy import select
from sqlalchemy.orm import Session
from app.models.client import Client
from app.schemas.client import ClientCreate
class ClientRepository:
    @staticmethod
    def list(db:Session)->list[Client]:return list(db.scalars(select(Client).order_by(Client.business_name)))
    @staticmethod
    def create(db:Session,payload:ClientCreate)->Client:
        c=Client(**payload.model_dump());db.add(c);db.commit();db.refresh(c);return c
    @staticmethod
    def get(db:Session,client_id:UUID)->Client|None:return db.get(Client,client_id)
