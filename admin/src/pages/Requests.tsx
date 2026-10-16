import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { collection, onSnapshot, query, where, updateDoc, doc, addDoc } from 'firebase/firestore';
import { db } from '../firebase';
import type { ServiceRequest, User } from '../types';

export default function Requests() {
  const navigate = useNavigate();
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [employees, setEmployees] = useState<User[]>([]);
  const [filter, setFilter] = useState<string>('all');
  const [loading, setLoading] = useState(true);
  const [selectedRequest, setSelectedRequest] = useState<ServiceRequest | null>(null);
  const [selectedEmployee, setSelectedEmployee] = useState<string>('');
  const [assignModalOpen, setAssignModalOpen] = useState(false);

  useEffect(() => {
    const q = query(collection(db, 'requests'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const requestsData: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        requestsData.push({ id: doc.id, ...doc.data() } as ServiceRequest);
      });
      setRequests(requestsData.sort((a, b) => 
        new Date(b.dateCreated).getTime() - new Date(a.dateCreated).getTime()
      ));
      setLoading(false);
    }, (error) => {
      console.error('Error fetching requests:', error);
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  useEffect(() => {
    const q = query(collection(db, 'users'), where('role', '==', 'employee'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const employeesData: User[] = [];
      snapshot.forEach((doc) => {
        employeesData.push({ uid: doc.id, ...doc.data() } as User);
      });
      setEmployees(employeesData);
    }, (error) => {
      console.error('Error fetching employees:', error);
    });

    return () => unsubscribe();
  }, []);

  const filteredRequests = filter === 'all'
    ? requests
    : requests.filter((r) => r.status === filter);

  const handleReject = async (request: ServiceRequest) => {
    if (!confirm('Are you sure you want to reject this request?')) return;
    
    try {
      await updateDoc(doc(db, 'requests', request.id), {
        status: 'Rejected',
      });
      
      await addDoc(collection(db, 'notifications'), {
        recipientId: request.customerId,
        requestId: request.id,
        message: `Request ${request.refNumber} has been rejected`,
        isRead: false,
        dateCreated: new Date().toISOString(),
      });
    } catch (error) {
      console.error('Error rejecting request:', error);
      alert('Failed to reject request');
    }
  };

  const handleAssign = async () => {
    if (!selectedRequest || !selectedEmployee) return;

    const employee = employees.find((e) => e.uid === selectedEmployee);
    if (!employee) return;

    // Check for scheduling conflicts
    const conflict = requests.some((r) => 
      r.employeeId === selectedEmployee &&
      r.requestedDate === selectedRequest.requestedDate &&
      (r.status === 'Approved' || r.status === 'In Progress')
    );

    if (conflict) {
      alert('This employee has another job on the same date. Please choose a different employee or date.');
      return;
    }

    try {
      await updateDoc(doc(db, 'requests', selectedRequest.id), {
        status: 'Approved',
        employeeId: selectedEmployee,
        employeeName: `${employee.firstName} ${employee.lastName}`,
      });

      await addDoc(collection(db, 'notifications'), {
        recipientId: selectedRequest.customerId,
        requestId: selectedRequest.id,
        message: `Request ${selectedRequest.refNumber} has been approved and assigned to ${employee.firstName} ${employee.lastName}`,
        isRead: false,
        dateCreated: new Date().toISOString(),
      });

      await addDoc(collection(db, 'notifications'), {
        recipientId: selectedEmployee,
        requestId: selectedRequest.id,
        message: `You have been assigned job #${selectedRequest.refNumber}`,
        isRead: false,
        dateCreated: new Date().toISOString(),
      });

      setAssignModalOpen(false);
      setSelectedRequest(null);
      setSelectedEmployee('');
    } catch (error) {
      console.error('Error assigning request:', error);
      alert('Failed to assign request');
    }
  };

  const openAssignModal = (request: ServiceRequest) => {
    setSelectedRequest(request);
    setAssignModalOpen(true);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pending': return 'bg-orange-100 text-orange-800';
      case 'Approved': return 'bg-blue-100 text-blue-800';
      case 'Rejected': return 'bg-red-100 text-red-800';
      case 'In Progress': return 'bg-purple-100 text-purple-800';
      case 'Completed': return 'bg-green-100 text-green-800';
      default: return 'bg-gray-100 text-gray-800';
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
      <header className="bg-green-700 text-white shadow-md">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">Phambili Ma Africa</h1>
            <p className="text-green-100 text-sm">Admin Dashboard</p>
          </div>
          <button
            onClick={() => navigate('/dashboard')}
            className="bg-green-600 hover:bg-green-800 px-4 py-2 rounded-md transition-colors"
          >
            Back to Dashboard
          </button>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-800">Service Requests</h2>
          <select
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            <option value="all">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <table className="min-w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ref
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Service
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Employee
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredRequests.map((request) => (
                <tr key={request.id}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {request.refNumber}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {request.serviceName}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {request.requestedDate}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(request.status)}`}>
                      {request.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {request.employeeName || 'Unassigned'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                    {request.status === 'Pending' && (
                      <>
                        <button
                          onClick={() => openAssignModal(request)}
                          className="text-green-600 hover:text-green-900 font-medium"
                        >
                          Assign
                        </button>
                        <button
                          onClick={() => handleReject(request)}
                          className="text-red-600 hover:text-red-900 font-medium"
                        >
                          Reject
                        </button>
                      </>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {filteredRequests.length === 0 && (
            <div className="text-center py-12 text-gray-500">
              No requests found
            </div>
          )}
        </div>
      </main>

      {/* Assign Modal */}
      {assignModalOpen && selectedRequest && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Assign Employee</h3>
            <p className="text-sm text-gray-600 mb-4">
              Request: {selectedRequest.refNumber} - {selectedRequest.serviceName}
            </p>
            
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Employee
            </label>
            <select
              value={selectedEmployee}
              onChange={(e) => setSelectedEmployee(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 mb-4"
            >
              <option value="">Choose an employee...</option>
              {employees
                .filter((e) => e.isAvailable !== false)
                .map((employee) => (
                  <option key={employee.uid} value={employee.uid}>
                    {employee.firstName} {employee.lastName} ({employee.phone || 'No phone'})
                  </option>
                ))}
            </select>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => {
                  setAssignModalOpen(false);
                  setSelectedRequest(null);
                  setSelectedEmployee('');
                }}
                className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleAssign}
                disabled={!selectedEmployee}
                className="px-4 py-2 bg-green-700 text-white rounded-md hover:bg-green-800 disabled:bg-gray-400"
              >
                Assign
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
