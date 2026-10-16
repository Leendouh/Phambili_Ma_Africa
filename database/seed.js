/**
 * Firebase Seed Data Script
 * Run this in Firebase Console: Functions → Import Data
 * Or use the Firebase CLI: firebase firestore:import database/seed-data.json
 *
 * This script creates initial data for:
 * - 1 Staff user (admin)
 * - 3 Employees
 * - 5 Services
 * - Test requests in various statuses
 */

const seedData = {
  "users": {
    // Staff user (create manually in Firebase Auth first, then use the UID)
    "STAFF_UID_HERE": {
      role: "staff",
      firstName: "Admin",
      lastName: "User",
      email: "admin@phambili-ma-africa.com",
      phone: "+27123456789",
      dateRegistered: new Date().toISOString()
    },
    // Employees (create manually in Firebase Auth first, then use the UIDs)
    "EMPLOYEE_UID_1": {
      role: "employee",
      firstName: "John",
      lastName: "Doe",
      email: "john@phambili-ma-africa.com",
      phone: "+27123456780",
      isAvailable: true,
      dateRegistered: new Date().toISOString()
    },
    "EMPLOYEE_UID_2": {
      role: "employee",
      firstName: "Jane",
      lastName: "Smith",
      email: "jane@phambili-ma-africa.com",
      phone: "+27123456781",
      isAvailable: true,
      dateRegistered: new Date().toISOString()
    },
    "EMPLOYEE_UID_3": {
      role: "employee",
      firstName: "Mike",
      lastName: "Johnson",
      email: "mike@phambili-ma-africa.com",
      phone: "+27123456782",
      isAvailable: false,
      dateRegistered: new Date().toISOString()
    }
  },
  "services": {
    "service1": {
      name: "Plumbing Repair",
      description: "Fix leaks, unclog drains, and repair pipes",
      price: 450.00,
      isActive: true
    },
    "service2": {
      name: "Electrical Installation",
      description: "Install light fixtures, outlets, and switches",
      price: 600.00,
      isActive: true
    },
    "service3": {
      name: "Painting Service",
      description: "Interior and exterior painting",
      price: 350.00,
      isActive: true
    },
    "service4": {
      name: "Garden Maintenance",
      description: "Lawn mowing, trimming, and general garden care",
      price: 250.00,
      isActive: true
    },
    "service5": {
      name: "Appliance Repair",
      description: "Repair and maintenance of household appliances",
      price: 400.00,
      isActive: true
    }
  },
  "requests": {
    // These are example requests - you'll need to update customer IDs with real customer UIDs
    "request1": {
      refNumber: "PMA-K9X2Z1",
      customerId: "CUSTOMER_UID_HERE",
      serviceId: "service1",
      serviceName: "Plumbing Repair",
      employeeId: "EMPLOYEE_UID_1",
      employeeName: "John Doe",
      staffId: "STAFF_UID_HERE",
      requestedDate: "2026-10-20",
      requestedTime: "09:00",
      siteAddress: "123 Main St, Johannesburg",
      status: "Pending",
      hasFeedback: false,
      dateCreated: new Date().toISOString()
    },
    "request2": {
      refNumber: "PMA-L8Y3W2",
      customerId: "CUSTOMER_UID_HERE",
      serviceId: "service2",
      serviceName: "Electrical Installation",
      employeeId: "EMPLOYEE_UID_2",
      employeeName: "Jane Smith",
      staffId: "STAFF_UID_HERE",
      requestedDate: "2026-10-18",
      requestedTime: "14:00",
      siteAddress: "456 Oak Ave, Cape Town",
      status: "Approved",
      hasFeedback: false,
      dateCreated: new Date(Date.now() - 86400000).toISOString()
    },
    "request3": {
      refNumber: "PMA-M7X4V3",
      customerId: "CUSTOMER_UID_HERE",
      serviceId: "service3",
      serviceName: "Painting Service",
      employeeId: "EMPLOYEE_UID_1",
      employeeName: "John Doe",
      staffId: "STAFF_UID_HERE",
      requestedDate: "2026-10-15",
      requestedTime: "10:00",
      siteAddress: "789 Pine Rd, Durban",
      status: "In Progress",
      hasFeedback: false,
      dateCreated: new Date(Date.now() - 172800000).toISOString()
    },
    "request4": {
      refNumber: "PMA-N6W5U4",
      customerId: "CUSTOMER_UID_HERE",
      serviceId: "service4",
      serviceName: "Garden Maintenance",
      employeeId: "EMPLOYEE_UID_2",
      employeeName: "Jane Smith",
      staffId: "STAFF_UID_HERE",
      requestedDate: "2026-10-10",
      requestedTime: "08:00",
      siteAddress: "321 Elm St, Pretoria",
      status: "Completed",
      hasFeedback: true,
      dateCreated: new Date(Date.now() - 432000000).toISOString()
    },
    "request5": {
      refNumber: "PMA-O5V6T5",
      customerId: "CUSTOMER_UID_HERE",
      serviceId: "service5",
      serviceName: "Appliance Repair",
      employeeId: null,
      employeeName: null,
      staffId: null,
      requestedDate: "2026-10-22",
      requestedTime: "11:00",
      siteAddress: "654 Maple Dr, Bloemfontein",
      status: "Rejected",
      hasFeedback: false,
      dateCreated: new Date(Date.now() - 259200000).toISOString()
    }
  },
  "feedback": {
    "request4": {
      customerId: "CUSTOMER_UID_HERE",
      rating: 5,
      comment: "Excellent service! Very professional and completed on time.",
      dateSubmitted: new Date(Date.now() - 431000000).toISOString()
    }
  },
  "notifications": {
    "notif1": {
      recipientId: "CUSTOMER_UID_HERE",
      requestId: "request2",
      message: "Your request #PMA-L8Y3W2 is now Approved",
      isRead: false,
      dateCreated: new Date(Date.now() - 80000000).toISOString()
    },
    "notif2": {
      recipientId: "CUSTOMER_UID_HERE",
      requestId: "request3",
      message: "Your request #PMA-M7X4V3 is now In Progress",
      isRead: false,
      dateCreated: new Date(Date.now() - 170000000).toISOString()
    },
    "notif3": {
      recipientId: "EMPLOYEE_UID_1",
      requestId: "request1",
      message: "You have been assigned job #PMA-K9X2Z1",
      isRead: true,
      dateCreated: new Date(Date.now() - 10000000).toISOString()
    }
  }
};

console.log("Seed data structure ready. Update UIDs with real Firebase Auth UIDs before importing.");
