from sqlalchemy import create_engine
from sqlalchemy.orm import DeclarativeBase,sessionmaker
from app.core.config import get_settings
engine=create_engine(get_settings().database_url,pool_pre_ping=True)
class Base(DeclarativeBase):pass
SessionLocal=sessionmaker(bind=engine,autoflush=False,autocommit=False)
def get_db():
    db=SessionLocal()
    try:yield db
    finally:db.close()
