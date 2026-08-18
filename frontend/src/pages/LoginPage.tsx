import {FormEvent,useState} from 'react';
import {login} from '../services/auth';

export default function LoginPage({onLogin}:{onLogin:()=>void}){
  const[username,setUsername]=useState('');
  const[password,setPassword]=useState('');
  const[error,setError]=useState('');
  const[loading,setLoading]=useState(false);

  async function submit(e:FormEvent){
    e.preventDefault();setError('');setLoading(true);
    try{await login(username,password);onLogin()}catch{setError('Usuario o contraseña incorrectos.')}finally{setLoading(false)}
  }

  return <div className="login-page"><form className="login-card" onSubmit={submit}><h1>BGV Enterprise</h1><p>Ingresa con tu cuenta</p><label>Usuario o correo<input value={username} onChange={e=>setUsername(e.target.value)} autoComplete="username" required/></label><label>Contraseña<input type="password" value={password} onChange={e=>setPassword(e.target.value)} autoComplete="current-password" required/></label>{error&&<p className="error">{error}</p>}<button disabled={loading}>{loading?'Ingresando...':'Ingresar'}</button></form></div>
}
