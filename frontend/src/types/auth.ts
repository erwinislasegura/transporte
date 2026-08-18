export type UserRole = 'admin' | 'operaciones' | 'consulta' | string;

export interface CurrentUser {
  id: string;
  username: string;
  full_name: string;
  email: string;
  role: UserRole;
  permissions: string[];
  menus: string[];
}

export interface LoginResponse {
  access_token: string;
  token_type: string;
  user: CurrentUser;
}
