from datetime import datetime,timedelta,timezone
from jose import jwt
from passlib.context import CryptContext
from app.core.config import get_settings
pwd_context=CryptContext(schemes=['bcrypt'],deprecated='auto')
ALGORITHM='HS256'
def hash_password(password:str)->str:return pwd_context.hash(password)
def verify_password(plain_password:str,password_hash:str)->bool:return pwd_context.verify(plain_password,password_hash)
def create_access_token(subject:str,expires_minutes:int|None=None)->str:
    s=get_settings(); exp=datetime.now(timezone.utc)+timedelta(minutes=expires_minutes or s.access_token_expire_minutes)
    return jwt.encode({'sub':subject,'exp':exp},s.jwt_secret_key,algorithm=ALGORITHM)
