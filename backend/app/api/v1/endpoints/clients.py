from uuid import UUID
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.core.rbac import require_permission
from app.db.session import get_db
from app.models.user import User
from app.schemas.client import ClientCreate, ClientRead
from app.repositories.client_repository import ClientRepository

router = APIRouter()


@router.get('', response_model=list[ClientRead])
def list_clients(
    db: Session = Depends(get_db),
    _user: User = Depends(require_permission('clients.view')),
):
    return ClientRepository.list(db)


@router.post('', response_model=ClientRead, status_code=status.HTTP_201_CREATED)
def create_client(
    payload: ClientCreate,
    db: Session = Depends(get_db),
    _user: User = Depends(require_permission('clients.create')),
):
    return ClientRepository.create(db, payload)


@router.get('/{client_id}', response_model=ClientRead)
def get_client(
    client_id: UUID,
    db: Session = Depends(get_db),
    _user: User = Depends(require_permission('clients.view')),
):
    c = ClientRepository.get(db, client_id)
    if c is None:
        raise HTTPException(status_code=404, detail='Cliente no encontrado')
    return c
