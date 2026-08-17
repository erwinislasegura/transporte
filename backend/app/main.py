from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.api.v1.router import api_router
from app.core.config import get_settings
s=get_settings()
app=FastAPI(title=s.app_name,version='1.0.0-foundation',description='API base de BGV Enterprise')
app.add_middleware(CORSMiddleware,allow_origins=s.cors_origin_list,allow_credentials=True,allow_methods=['*'],allow_headers=['*'])
app.include_router(api_router,prefix='/api/v1')
@app.get('/')
def root():return {'product':'BGV Enterprise','version':'1.0 Foundation','docs':'/docs'}
