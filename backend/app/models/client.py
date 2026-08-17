import uuid
from datetime import datetime
from sqlalchemy import String,DateTime,Boolean,ForeignKey
from sqlalchemy.orm import Mapped,mapped_column
from sqlalchemy.dialects.postgresql import UUID
from app.db.session import Base
class Client(Base):
    __tablename__='clients'
    id:Mapped[uuid.UUID]=mapped_column(UUID(as_uuid=True),primary_key=True,default=uuid.uuid4)
    company_id:Mapped[uuid.UUID]=mapped_column(ForeignKey('companies.id'),index=True)
    code:Mapped[str]=mapped_column(String(30),index=True)
    rut:Mapped[str]=mapped_column(String(12),index=True)
    business_name:Mapped[str]=mapped_column(String(180))
    payment_condition:Mapped[str]=mapped_column(String(80),default='Transferencia 30 días')
    requires_oc:Mapped[str]=mapped_column(String(20),default='Opcional')
    requires_hes:Mapped[str]=mapped_column(String(20),default='Opcional')
    active:Mapped[bool]=mapped_column(Boolean,default=True)
    created_at:Mapped[datetime]=mapped_column(DateTime,default=datetime.utcnow)
