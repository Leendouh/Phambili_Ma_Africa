import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { collection, onSnapshot, query } from 'firebase/firestore';
import { db } from '../firebase';
import type { ServiceRequest, Feedback } from '../types';

export default function Reports() {
  const navigate = useNavigate();
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [feedback, setFeedback] = useState<Feedback[]>([]);
  const [loading, setLoading] = useState(true);
  const [dateFilter, setDateFilter] = useState('');
  const [serviceFilter, setServiceFilter] = useState('');

  useEffect(() => {
    const q = query(collection(db, 'requests'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const requestsData: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        requestsData.push({ id: doc.id, ...doc.data() } as ServiceRequest);
      });
      setRequests(requestsData);
      setLoading(false);
    }, (error) => {
      console.error('Error fetching requests:', error);
      setLoading(false);
    });

    return unsubscribe;
  }, []);

  useEffect(() => {
    const q = query(collection(db, 'feedback'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const feedbackData: Feedback[] = [];
      snapshot.forEach((doc) => {
        feedbackData.push({ requestId: doc.id, ...doc.data() } as Feedback);
      });
      setFeedback(feedbackData);
    }, (error) => {
      console.error('Error fetching feedback:', error);
    });

    return unsubscribe;
  }, []);

  const filteredFeedback = feedback.filter((f) => {
    const request = requests.find((r) => r.id === f.requestId);
    if (!request) return false;

    if (dateFilter && !request.requestedDate.startsWith(dateFilter)) return false;
    if (serviceFilter && request.serviceId !== serviceFilter) return false;

    return true;
  });

  const averageRating = feedback.length > 0
    ? (feedback.reduce((sum, f) => sum + f.rating, 0) / feedback.length).toFixed(1)
    : '0.0';

  const requestsByService = requests.reduce((acc, request) => {
    acc[request.serviceName] = (acc[request.serviceName] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  const mostRequestedServices = Object.entries(requestsByService)
    .sort(([, a], [, b]) => b - a)
    .slice(0, 5);

  const exportCSV = () => {
    const headers = ['Request ID', 'Ref Number', 'Service', 'Date', 'Status', 'Rating', 'Comment'];
    const rows = filteredFeedback.map((f) => {
      const request = requests.find((r) => r.id === f.requestId);
      return [
        f.requestId,
        request?.refNumber || '',
        request?.serviceName || '',
        request?.requestedDate || '',
        request?.status || '',
        f.rating,
        f.comment || '',
      ];
    });

    const csvCell = (value: unknown) => {
      let text = String(value ?? '');
      if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`;
      return `"${text.replace(/"/g, '""')}"`;
    };

    const csvContent = [
      headers.join(','),
      ...rows.map((row) => row.map(csvCell).join(',')),
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `feedback_report_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
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
        <h2 className="text-2xl font-bold text-gray-800 mb-6">Reports & Analytics</h2>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Total Requests</h3>
            <p className="text-4xl font-bold text-brand-700">{requests.length}</p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Average Rating</h3>
            <p className="text-4xl font-bold text-blue-700">{averageRating} / 5</p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Total Feedback</h3>
            <p className="text-4xl font-bold text-purple-700">{feedback.length}</p>
          </div>
        </div>

        {/* Most Requested Services */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-8">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Most Requested Services</h3>
          {mostRequestedServices.length > 0 ? (
            <div className="space-y-3">
              {mostRequestedServices.map(([serviceName, count], index) => (
                <div key={serviceName} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                  <div className="flex items-center">
                    <span className="text-2xl font-bold text-brand-700 mr-4">{index + 1}</span>
                    <span className="font-medium">{serviceName}</span>
                  </div>
                  <span className="text-gray-600">{count} requests</span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500">No requests yet</p>
          )}
        </div>

        {/* Feedback Table */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-semibold text-gray-800">Customer Feedback</h3>
            <button
              onClick={exportCSV}
              className="bg-brand-700 text-white px-4 py-2 rounded-md hover:bg-brand-800 transition-colors"
            >
              Export CSV
            </button>
          </div>

          <div className="flex space-x-4 mb-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Filter by Date</label>
              <input
                type="date"
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Filter by Service</label>
              <select
                value={serviceFilter}
                onChange={(e) => setServiceFilter(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option value="">All Services</option>
                {Array.from(new Set(requests.map((r) => r.serviceName))).map((serviceName) => (
                  <option key={serviceName} value={serviceName}>
                    {serviceName}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="overflow-x-auto">
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
                    Rating
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Comment
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredFeedback.map((f) => {
                  const request = requests.find((r) => r.id === f.requestId);
                  if (!request) return null;

                  return (
                    <tr key={f.requestId}>
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
                        <div className="flex items-center">
                          <span className="text-yellow-500 mr-1">★</span>
                          <span className="text-sm font-medium">{f.rating}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                        {f.comment || 'No comment'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>

            {filteredFeedback.length === 0 && (
              <div className="text-center py-12 text-gray-500">
                No feedback found
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
