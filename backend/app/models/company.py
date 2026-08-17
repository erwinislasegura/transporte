import uuid
from datetime import datetime
from sqlalchemy import String,DateTime,Boolean
from sqlalchemy.orm import Mapped,mapped_column
from sqlalchemy.dialects.postgresql import UUID
from app.db.session import Base
class Company(Base):
    __tablename__='companies'
    id:Mapped[uuid.UUID]=mapped_column(UUID(as_uuid=True),primary_key=True,default=uuid.uuid4)
    rut:Mapped[str]=mapped_column(String(12),unique=True,index=True)
    legal_name:Mapped[str]=mapped_column(String(180))
    trade_name:Mapped[str|None]=mapped_column(String(180),nullable=True)
    active:Mapped[bool]=mapped_column(Boolean,default=True)
    created_at:Mapped[datetime]=mapped_column(DateTime,default=datetime.utcnow)
