import {api} from './api';
import type {CurrentUser, LoginResponse} from '../types/auth';

const TOKEN_KEY='bgv_access_token';
const USER_KEY='bgv_current_user';

export function getToken(){return localStorage.getItem(TOKEN_KEY)}
export function getStoredUser():CurrentUser|null{const raw=localStorage.getItem(USER_KEY);if(!raw)return null;try{return JSON.parse(raw) as CurrentUser}catch{return null}}
export function hasPermission(user:CurrentUser|null,permission:string){return Boolean(user?.permissions.includes(permission))}
export function canSeeMenu(user:CurrentUser|null,menu:string){return Boolean(user?.menus.includes(menu))}

export async function login(username:string,password:string){
  const {data}=await api.post<LoginResponse>('/auth/login',{username,password});
  localStorage.setItem(TOKEN_KEY,data.access_token);
  localStorage.setItem(USER_KEY,JSON.stringify(data.user));
  return data.user;
}

export async function refreshCurrentUser(){
  const {data}=await api.get<CurrentUser>('/auth/me');
  localStorage.setItem(USER_KEY,JSON.stringify(data));
  return data;
}

export function logout(){localStorage.removeItem(TOKEN_KEY);localStorage.removeItem(USER_KEY)}
