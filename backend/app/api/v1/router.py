from fastapi import APIRouter
from app.api.v1.endpoints import auth, health, clients

api_router = APIRouter()
api_router.include_router(health.router, tags=['Health'])
api_router.include_router(auth.router, prefix='/auth', tags=['Auth'])
api_router.include_router(clients.router, prefix='/clients', tags=['Clients'])
