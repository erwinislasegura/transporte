import axios from 'axios';
import {getToken} from './auth';

export const api=axios.create({baseURL:import.meta.env.VITE_API_URL??'http://localhost:8000/api/v1',timeout:10000});

api.interceptors.request.use(config=>{
  const token=getToken();
  if(token)config.headers.Authorization=`Bearer ${token}`;
  return config;
});
