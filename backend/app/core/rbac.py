from collections.abc import Callable
from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from jose import JWTError, jwt
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import ALGORITHM
from app.db.session import get_db
from app.models.user import User

oauth2_scheme = OAuth2PasswordBearer(tokenUrl='/api/v1/auth/login')

ROLE_PERMISSIONS: dict[str, set[str]] = {
    'admin': {'dashboard.view', 'clients.view', 'clients.create', 'users.manage'},
    'operaciones': {'dashboard.view', 'clients.view', 'clients.create'},
    'consulta': {'dashboard.view'},
}

MENU_BY_PERMISSION = {
    'dashboard.view': 'dashboard',
    'clients.view': 'clients',
    'users.manage': 'users',
}


def get_current_user(token: str = Depends(oauth2_scheme), db: Session = Depends(get_db)) -> User:
    credentials_error = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail='Sesión inválida o expirada',
        headers={'WWW-Authenticate': 'Bearer'},
    )
    try:
        payload = jwt.decode(token, get_settings().jwt_secret_key, algorithms=[ALGORITHM])
        user_id = payload.get('sub')
        if not user_id:
            raise credentials_error
    except JWTError as exc:
        raise credentials_error from exc

    user = db.scalar(select(User).where(User.id == user_id))
    if user is None or not user.active:
        raise credentials_error
    return user


def require_permission(permission: str) -> Callable:
    def dependency(user: User = Depends(get_current_user)) -> User:
        permissions = ROLE_PERMISSIONS.get(user.role.lower(), set())
        if permission not in permissions:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail='No tienes permisos para esta acción')
        return user
    return dependency


def permissions_for_role(role: str) -> list[str]:
    return sorted(ROLE_PERMISSIONS.get(role.lower(), set()))


def menus_for_role(role: str) -> list[str]:
    permissions = ROLE_PERMISSIONS.get(role.lower(), set())
    return [menu for permission, menu in MENU_BY_PERMISSION.items() if permission in permissions]
