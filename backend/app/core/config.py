from functools import lru_cache
from pydantic_settings import BaseSettings, SettingsConfigDict
class Settings(BaseSettings):
    app_name:str='BGV Enterprise API'
    database_url:str='postgresql+psycopg://bgv_user:change_me@db:5432/bgv_enterprise'
    jwt_secret_key:str='change_me'
    access_token_expire_minutes:int=60
    cors_origins:str='http://localhost:5173'
    model_config=SettingsConfigDict(env_file='.env',extra='ignore')
    @property
    def cors_origin_list(self)->list[str]:
        return [x.strip() for x in self.cors_origins.split(',') if x.strip()]
@lru_cache
def get_settings()->Settings:
    return Settings()
