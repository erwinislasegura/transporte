import {useState} from 'react';
import {Navigate,NavLink,Route,Routes} from 'react-router-dom';
import DashboardPage from './pages/DashboardPage';
import ClientsPage from './pages/ClientsPage';
import LoginPage from './pages/LoginPage';
import {canSeeMenu,getStoredUser,logout} from './services/auth';
import type {CurrentUser} from './types/auth';

function ProtectedRoute({user,menu,children}:{user:CurrentUser|null;menu:string;children:JSX.Element}){
  if(!user)return <Navigate to="/login" replace/>;
  if(!canSeeMenu(user,menu))return <Navigate to="/" replace/>;
  return children;
}

export default function App(){
  const[user,setUser]=useState<CurrentUser|null>(getStoredUser());

  function handleLogout(){logout();setUser(null)}

  if(!user){return <Routes><Route path="/login" element={<LoginPage onLogin={()=>setUser(getStoredUser())}/>}/><Route path="*" element={<Navigate to="/login" replace/>}/></Routes>}

  return <div className="app-shell"><aside><h1>BGV Enterprise</h1><p>{user.full_name}</p><small>{user.role}</small><nav>{canSeeMenu(user,'dashboard')&&<NavLink to="/">Dashboard</NavLink>}{canSeeMenu(user,'clients')&&<NavLink to="/clients">Clientes</NavLink>}</nav><button className="logout-button" onClick={handleLogout}>Cerrar sesión</button></aside><main><Routes><Route path="/" element={<ProtectedRoute user={user} menu="dashboard"><DashboardPage/></ProtectedRoute>}/><Route path="/clients" element={<ProtectedRoute user={user} menu="clients"><ClientsPage/></ProtectedRoute>}/><Route path="/login" element={<Navigate to="/" replace/>}/><Route path="*" element={<Navigate to="/" replace/>}/></Routes></main></div>
}
