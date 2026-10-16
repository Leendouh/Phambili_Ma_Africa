import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { collection, onSnapshot, query, where, updateDoc, doc } from 'firebase/firestore';
import { db } from '../firebase';
import type { User, ServiceRequest } from '../types';

export default function Employees() {
  const navigate = useNavigate();
  const [employees, setEmployees] = useState<User[]>([]);
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<User | null>(null);
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    isAvailable: true,
  });

  useEffect(() => {
    const q = query(collection(db, 'users'), where('role', '==', 'employee'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const employeesData: User[] = [];
      snapshot.forEach((doc) => {
        employeesData.push({ uid: doc.id, ...doc.data() } as User);
      });
      setEmployees(employeesData);
      setLoading(false);
    }, (error) => {
      console.error('Error fetching employees:', error);
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  useEffect(() => {
    const q = query(collection(db, 'requests'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const requestsData: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        requestsData.push({ id: doc.id, ...doc.data() } as ServiceRequest);
      });
      setRequests(requestsData);
    }, (error) => {
      console.error('Error fetching requests:', error);
    });

    return () => unsubscribe();
  }, []);

  const getEmployeeJobs = (employeeId: string) => {
    return requests.filter((r) => r.employeeId === employeeId);
  };

  const handleToggleAvailability = async (employee: User) => {
    try {
      await updateDoc(doc(db, 'users', employee.uid), {
        isAvailable: !employee.isAvailable,
      });
    } catch (error) {
      console.error('Error updating availability:', error);
      alert('Failed to update availability');
    }
  };

  const openEditModal = (employee: User) => {
    setSelectedEmployee(employee);
    setFormData({
      firstName: employee.firstName,
      lastName: employee.lastName,
      email: employee.email,
      phone: employee.phone || '',
      isAvailable: employee.isAvailable ?? true,
    });
    setEditModalOpen(true);
  };

  const handleUpdateEmployee = async () => {
    if (!selectedEmployee) return;

    try {
      await updateDoc(doc(db, 'users', selectedEmployee.uid), {
        firstName: formData.firstName,
        lastName: formData.lastName,
        phone: formData.phone,
        isAvailable: formData.isAvailable,
      });
      setEditModalOpen(false);
      setSelectedEmployee(null);
    } catch (error) {
      console.error('Error updating employee:', error);
      alert('Failed to update employee');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-gray-600">Loading...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <header className="bg-brand-700 text-white shadow-md">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">Phambili Ma Africa</h1>
            <p className="text-brand-100 text-sm">Admin Dashboard</p>
          </div>
          <button
            onClick={() => navigate('/dashboard')}
            className="bg-brand-600 hover:bg-brand-800 px-4 py-2 rounded-md transition-colors"
          >
            Back to Dashboard
          </button>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6">Employees</h2>

        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <table className="min-w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Email
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Phone
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Availability
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Active Jobs
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {employees.map((employee) => (
                <tr key={employee.uid}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {employee.firstName} {employee.lastName}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {employee.email}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {employee.phone || 'N/A'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <button
                      onClick={() => handleToggleAvailability(employee)}
                      className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        employee.isAvailable
                          ? 'bg-green-100 text-green-800'
                          : 'bg-red-100 text-red-800'
                      }`}
                    >
                      {employee.isAvailable ? 'Available' : 'Unavailable'}
                    </button>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {getEmployeeJobs(employee.uid).length}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                    <button
                      onClick={() => openEditModal(employee)}
                      className="text-blue-600 hover:text-blue-900 font-medium"
                    >
                      Edit
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {employees.length === 0 && (
            <div className="text-center py-12 text-gray-500">
              No employees found
            </div>
          )}
        </div>

        {/* Employee Jobs Section */}
        {employees.map((employee) => {
          const jobs = getEmployeeJobs(employee.uid);
          if (jobs.length === 0) return null;

          return (
            <div key={employee.uid} className="mt-8 bg-white rounded-lg shadow-md p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">
                {employee.firstName} {employee.lastName}'s Jobs
              </h3>
              <div className="space-y-3">
                {jobs.map((job) => (
                  <div
                    key={job.id}
                    className="flex justify-between items-center p-3 bg-gray-50 rounded"
                  >
                    <div>
                      <p className="font-medium">{job.refNumber}</p>
                      <p className="text-sm text-gray-600">{job.serviceName}</p>
                      <p className="text-sm text-gray-500">
                        {job.requestedDate} at {job.requestedTime}
                      </p>
                    </div>
                    <span
                      className={`px-2 py-1 text-xs font-semibold rounded-full ${
                        job.status === 'Completed'
                          ? 'bg-green-100 text-green-800'
                          : job.status === 'In Progress'
                          ? 'bg-purple-100 text-purple-800'
                          : 'bg-blue-100 text-blue-800'
                      }`}
                    >
                      {job.status}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </main>

      {/* Edit Modal */}
      {editModalOpen && selectedEmployee && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Edit Employee</h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  First Name
                </label>
                <input
                  type="text"
                  value={formData.firstName}
                  onChange={(e) => setFormData({ ...formData, firstName: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Last Name
                </label>
                <input
                  type="text"
                  value={formData.lastName}
                  onChange={(e) => setFormData({ ...formData, lastName: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Phone
                </label>
                <input
                  type="text"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div className="flex items-center">
                <input
                  type="checkbox"
                  id="isAvailable"
                  checked={formData.isAvailable}
                  onChange={(e) => setFormData({ ...formData, isAvailable: e.target.checked })}
                  className="mr-2"
                />
                <label htmlFor="isAvailable" className="text-sm text-gray-700">
                  Available for assignments
                </label>
              </div>
            </div>

            <div className="flex justify-end space-x-3 mt-6">
              <button
                onClick={() => {
                  setEditModalOpen(false);
                  setSelectedEmployee(null);
                }}
                className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleUpdateEmployee}
                className="px-4 py-2 bg-brand-700 text-white rounded-md hover:bg-brand-800"
              >
                Update
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
