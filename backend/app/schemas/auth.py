from uuid import UUID
from pydantic import BaseModel


class LoginRequest(BaseModel):
    username: str
    password: str


class CurrentUserResponse(BaseModel):
    id: UUID
    username: str
    full_name: str
    email: str
    role: str
    permissions: list[str]
    menus: list[str]


class LoginResponse(BaseModel):
    access_token: str
    token_type: str = 'bearer'
    user: CurrentUserResponse
