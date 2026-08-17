import uuid
from datetime import datetime
from sqlalchemy import String,DateTime,Boolean,ForeignKey
from sqlalchemy.orm import Mapped,mapped_column
from sqlalchemy.dialects.postgresql import UUID
from app.db.session import Base
class User(Base):
    __tablename__='users'
    id:Mapped[uuid.UUID]=mapped_column(UUID(as_uuid=True),primary_key=True,default=uuid.uuid4)
    company_id:Mapped[uuid.UUID]=mapped_column(ForeignKey('companies.id'),index=True)
    username:Mapped[str]=mapped_column(String(80),unique=True,index=True)
    full_name:Mapped[str]=mapped_column(String(180))
    email:Mapped[str]=mapped_column(String(180),unique=True,index=True)
    password_hash:Mapped[str]=mapped_column(String(255))
    role:Mapped[str]=mapped_column(String(60),index=True)
    active:Mapped[bool]=mapped_column(Boolean,default=True)
    created_at:Mapped[datetime]=mapped_column(DateTime,default=datetime.utcnow)
