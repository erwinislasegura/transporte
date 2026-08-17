from uuid import UUID
from pydantic import BaseModel,Field
class ClientBase(BaseModel):
    code:str=Field(min_length=3,max_length=30)
    rut:str=Field(min_length=8,max_length=12)
    business_name:str=Field(min_length=2,max_length=180)
    payment_condition:str='Transferencia 30 días'
    requires_oc:str='Opcional'
    requires_hes:str='Opcional'
class ClientCreate(ClientBase):company_id:UUID
class ClientRead(ClientBase):
    id:UUID
    company_id:UUID
    active:bool
    model_config={'from_attributes':True}
