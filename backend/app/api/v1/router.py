from fastapi import APIRouter
from app.api.v1.endpoints import health,clients
api_router=APIRouter()
api_router.include_router(health.router,tags=['Health'])
api_router.include_router(clients.router,prefix='/clients',tags=['Clients'])
