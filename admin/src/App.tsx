import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import RequireStaff from './components/RequireStaff';
import Login from './pages/Login';
import Verify from './pages/Verify';
import Dashboard from './pages/Dashboard';
import Requests from './pages/Requests';
import Employees from './pages/Employees';
import Services from './pages/Services';
import Customers from './pages/Customers';
import Reports from './pages/Reports';

const staffOnly = (page: React.ReactNode) => <RequireStaff>{page}</RequireStaff>;

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/verify" element={<Verify />} />
          <Route path="/dashboard" element={staffOnly(<Dashboard />)} />
          <Route path="/requests" element={staffOnly(<Requests />)} />
          <Route path="/employees" element={staffOnly(<Employees />)} />
          <Route path="/services" element={staffOnly(<Services />)} />
          <Route path="/customers" element={staffOnly(<Customers />)} />
          <Route path="/reports" element={staffOnly(<Reports />)} />
          <Route path="/" element={<Navigate to="/login" replace />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;
