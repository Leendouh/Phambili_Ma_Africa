// Firestore data helpers for the website.
// Firestore docs use camelCase (shared with the mobile app + admin
// dashboard). Helpers below map to the field names the PHP pages
// used (Name, Description, ...) so templates stay readable.

import {
    collection,
    doc,
    getDoc,
    getDocs,
    addDoc,
    setDoc,
    updateDoc,
    deleteDoc,
    query,
    where,
    orderBy,
    onSnapshot,
    serverTimestamp,
} from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-firestore.js';
import { db } from './app.js';

/* ----------------------------- Services ------------------------------ */

function mapService(id, d) {
    return {
        ID: id,
        Name: d.name ?? '',
        Description: d.description ?? '',
        Price: d.price ?? null,
        Duration: d.duration ?? null,
        Category: d.category ?? 'General',
        Is_Available: d.isActive ?? d.isAvailable ?? true,
        Image_URL: d.imageUrl ?? d.imageURL ?? '',
        _raw: d,
    };
}

export async function getServices() {
    const snap = await getDocs(collection(db, 'services'));
    const services = snap.docs.map((s) => mapService(s.id, s.data()));
    services.sort((a, b) => a.Category.localeCompare(b.Category) || a.Name.localeCompare(b.Name));
    return services;
}

export function watchServices(cb) {
    return onSnapshot(collection(db, 'services'), (snap) => {
        const services = snap.docs.map((s) => mapService(s.id, s.data()));
        services.sort((a, b) => a.Category.localeCompare(b.Category) || a.Name.localeCompare(b.Name));
        cb(services);
    });
}

export async function getService(id) {
    const snap = await getDoc(doc(db, 'services', id));
    return snap.exists() ? mapService(snap.id, snap.data()) : null;
}

/* ----------------------------- Products ------------------------------ */

function mapProduct(id, d) {
    return {
        ID: id,
        Name: d.name ?? '',
        Description: d.description ?? '',
        Price: d.price ?? 0,
        Stock_Quantity: d.stockQuantity ?? d.stock ?? 0,
        Category: d.category ?? 'General',
        Is_Available: d.isActive ?? d.isAvailable ?? true,
        Image_URL: d.imageUrl ?? d.imageURL ?? '',
        _raw: d,
    };
}

export async function getProducts() {
    const snap = await getDocs(collection(db, 'products'));
    const products = snap.docs.map((p) => mapProduct(p.id, p.data()));
    products.sort((a, b) => a.Category.localeCompare(b.Category) || a.Name.localeCompare(b.Name));
    return products;
}

export function watchProducts(cb) {
    return onSnapshot(collection(db, 'products'), (snap) => {
        const products = snap.docs.map((p) => mapProduct(p.id, p.data()));
        products.sort((a, b) => a.Category.localeCompare(b.Category) || a.Name.localeCompare(b.Name));
        cb(products);
    });
}

/* -------------------- Service / product writes (staff) ----------------- */
// Stored camelCase to match the shared mobile/admin model.

export async function createService(data) {
    const ref = await addDoc(collection(db, 'services'), {
        name: data.Name || '',
        description: data.Description || '',
        duration: Number(data.Duration) || 0,
        category: data.Category || 'General',
        isActive: !!data.Is_Available,
        imageUrl: data.Image_URL || '',
    });
    return ref.id;
}

export async function updateService(id, data) {
    await updateDoc(doc(db, 'services', id), {
        name: data.Name || '',
        description: data.Description || '',
        duration: Number(data.Duration) || 0,
        category: data.Category || 'General',
        isActive: !!data.Is_Available,
        imageUrl: data.Image_URL || '',
    });
}

export async function setServiceAvailability(id, isAvailable) {
    await updateDoc(doc(db, 'services', id), { isActive: !!isAvailable });
}

export async function deleteService(id) {
    await deleteDoc(doc(db, 'services', id));
}

export async function createProduct(data) {
    const ref = await addDoc(collection(db, 'products'), {
        name: data.Name || '',
        description: data.Description || '',
        price: Number(data.Price) || 0,
        stockQuantity: Number(data.Stock_Quantity) || 0,
        category: data.Category || 'General',
        isActive: !!data.Is_Available,
        imageUrl: data.Image_URL || '',
    });
    return ref.id;
}

export async function updateProduct(id, data) {
    await updateDoc(doc(db, 'products', id), {
        name: data.Name || '',
        description: data.Description || '',
        price: Number(data.Price) || 0,
        stockQuantity: Number(data.Stock_Quantity) || 0,
        category: data.Category || 'General',
        isActive: !!data.Is_Available,
        imageUrl: data.Image_URL || '',
    });
}

export async function setProductAvailability(id, isAvailable) {
    await updateDoc(doc(db, 'products', id), { isActive: !!isAvailable });
}

export async function deleteProduct(id) {
    await deleteDoc(doc(db, 'products', id));
}

/* ------------------------- Requests / Bookings ------------------------ */
// Website "bookings" and mobile "requests" share the `requests`
// collection. Status is Title-case to match mobile/admin:
//   Pending → Contacted → Quoted → Confirmed → In Progress → Completed
//   (+ Rejected / Cancelled / Expired)
// Website pages can display all of these; mobile only drives
// Pending/Approved/In Progress/Completed.

function mapRequest(id, d) {
    return {
        ID: id,
        refNumber: d.refNumber ?? '',
        Customer_ID: d.customerId ?? '',
        Service_ID: d.serviceId ?? '',
        serviceName: d.serviceName ?? '',
        Date: d.requestedDate ?? '',
        Time: d.requestedTime ?? '',
        Address: d.siteAddress ?? '',
        Status: d.status ?? 'Pending',
        Special_Instructions: d.specialInstructions ?? '',
        Quoted_Amount: d.quotedAmount ?? null,
        Property_Type: d.propertyType ?? '',
        Property_Size: d.propertySize ?? '',
        Cleaning_Frequency: d.cleaningFrequency ?? '',
        Notes: d.adminNotes ?? d.notes ?? '',
        customerName: d.customerName ?? '',
        customerPhone: d.customerPhone ?? '',
        customerEmail: d.customerEmail ?? '',
        // Legacy admin-dashboard field aliases (old MySQL column names).
        Customer_Name: d.customerName ?? '',
        Customer_Email: d.customerEmail ?? '',
        Customer_Phone: d.customerPhone ?? '',
        Service_Name: d.serviceName ?? '',
        created_at: d.dateCreated ?? '',
        employeeId: d.employeeId ?? null,
        employeeName: d.employeeName ?? null,
        quote_breakdown: d.quoteBreakdown ?? '',
        quote_validity_days: d.quoteValidityDays ?? 7,
        quote_notes: d.quoteNotes ?? '',
        quoteSentDate: d.quoteSentDate ?? '',
        dateCreated: d.dateCreated ?? '',
        _raw: d,
    };
}

export async function createRequest(profile, data) {
    const refNumber = 'PMA-' + Date.now().toString(36).toUpperCase();
    const docData = {
        refNumber,
        customerId: profile.uid,
        customerName: `${profile.firstName || ''} ${profile.lastName || ''}`.trim(),
        customerPhone: data.customerPhone || profile.phone || '',
        customerEmail: data.customerEmail || profile.email || '',
        serviceId: data.serviceId,
        serviceName: data.serviceName || '',
        employeeId: null,
        employeeName: null,
        staffId: null,
        requestedDate: data.requestedDate,
        requestedTime: data.requestedTime,
        siteAddress: data.siteAddress,
        specialInstructions: data.specialInstructions || '',
        propertyType: data.propertyType || '',
        propertySize: data.propertySize || '',
        cleaningFrequency: data.cleaningFrequency || '',
        addressStreet: data.addressStreet || '',
        addressCity: data.addressCity || '',
        addressState: data.addressState || '',
        addressPostal: data.addressPostal || '',
        quoteRequested: !!data.quoteRequested,
        status: 'Pending',
        hasFeedback: false,
        source: 'web',
        dateCreated: new Date().toISOString(),
    };
    const ref = await addDoc(collection(db, 'requests'), docData);
    return { id: ref.id, ...docData };
}

// Future booked dates/times for a service, shaped like the legacy
// api/bookings.php?action=get_availability payload for booking-calendar.js.
// Closed statuses (Cancelled/Rejected/Expired) free the slot again.
export async function getServiceAvailability(serviceId) {
    const today = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const q = query(
        requestsCol,
        where('serviceId', '==', String(serviceId)),
        where('requestedDate', '>=', fmt(today))
    );
    const snap = await getDocs(q);
    const closed = new Set(['Cancelled', 'Rejected', 'Expired']);
    const bookedSlots = {};
    snap.docs.forEach((d) => {
        const r = d.data();
        if (closed.has(r.status) || !r.requestedDate) return;
        (bookedSlots[r.requestedDate] = bookedSlots[r.requestedDate] || [])
            .push(r.requestedTime || '');
    });
    const availableDates = [];
    for (let i = 0; i < 60; i++) {
        const ds = fmt(new Date(today.getFullYear(), today.getMonth(), today.getDate() + i));
        if ((bookedSlots[ds] || []).length < 10) availableDates.push(ds);
    }
    return { success: true, available_dates: availableDates, booked_slots: bookedSlots };
}

// Live list of the signed-in customer's requests (sorted client-side
// — Firestore can't where+orderBy different fields without an index).
export function watchMyRequests(uid, cb) {
    const q = query(collection(db, 'requests'), where('customerId', '==', uid));
    return onSnapshot(q, (snap) => {
        const requests = snap.docs.map((r) => mapRequest(r.id, r.data()));
        requests.sort((a, b) => (b._raw.dateCreated || '').localeCompare(a._raw.dateCreated || ''));
        cb(requests);
    });
}

export async function getRequest(id) {
    const snap = await getDoc(doc(db, 'requests', id));
    return snap.exists() ? mapRequest(snap.id, snap.data()) : null;
}

export async function updateRequest(id, fields) {
    await updateDoc(doc(db, 'requests', id), fields);
}

// True if this customer already has an active request for the same
// service on the same date (mirrors the old MySQL duplicate check).
export async function hasDuplicateRequest(uid, serviceId, date) {
    const q = query(
        collection(db, 'requests'),
        where('customerId', '==', uid),
        where('serviceId', '==', serviceId),
        where('requestedDate', '==', date)
    );
    const snap = await getDocs(q);
    return snap.docs.some((r) => !['Cancelled', 'Rejected', 'Expired'].includes(r.data().status));
}

/* ----------------------- Staff / admin dashboard ----------------------- */

// Live list of ALL requests (staff only — rules enforce this).
export function watchAllRequests(cb) {
    return onSnapshot(collection(db, 'requests'), (snap) => {
        const requests = snap.docs.map((r) => mapRequest(r.id, r.data()));
        requests.sort((a, b) => (b.dateCreated || '').localeCompare(a.dateCreated || ''));
        cb(requests);
    });
}

// Customers = users docs with role 'customer' (staff read allowed).
function mapCustomerDoc(id, d) {
    return {
        ID: id,
        Full_Name: `${d.firstName || ''} ${d.lastName || ''}`.trim() || d.email || 'Customer',
        Email: d.email ?? '',
        Phone: d.phone ?? '',
        Address: d.address ?? '',
        createdAt: d.dateRegistered ?? '',
        _raw: d,
    };
}

export function watchCustomers(cb) {
    const q = query(collection(db, 'users'), where('role', '==', 'customer'));
    return onSnapshot(q, (snap) => {
        const customers = snap.docs.map((u) => mapCustomerDoc(u.id, u.data()));
        customers.sort((a, b) => (b.createdAt || '').localeCompare(a.createdAt || ''));
        cb(customers);
    });
}

// Employees = users docs with role 'employee'.
function mapEmployeeDoc(id, d) {
    return {
        ID: id,
        Full_Name: `${d.firstName || ''} ${d.lastName || ''}`.trim() || d.email || 'Employee',
        Email: d.email ?? '',
        Phone: d.phone ?? '',
        Is_Available: d.isAvailable ?? true,
        createdAt: d.dateRegistered ?? '',
        _raw: d,
    };
}

export function watchEmployees(cb) {
    const q = query(collection(db, 'users'), where('role', '==', 'employee'));
    return onSnapshot(q, (snap) => {
        cb(snap.docs.map((u) => mapEmployeeDoc(u.id, u.data())));
    });
}

export async function setEmployeeAvailability(id, isAvailable) {
    await updateDoc(doc(db, 'users', id), { isAvailable: !!isAvailable });
}

/* ------------------------- Admin accounts ---------------------------- */
// `admins` collection mirrors the old MySQL `admins` table; doc id is the
// Firebase Auth uid and the matching users/{uid} doc holds role 'staff'.

function mapAdminDoc(id, d) {
    return {
        ID: id,
        Full_Name: d.fullName ?? '',
        Email: d.email ?? '',
        Phone: d.phone ?? '',
        Role: d.adminRole ?? 'sub_admin',
        first_login: d.firstLogin ?? false,
        isActive: d.isActive ?? true,
        createdAt: d.createdAt ?? '',
        updatedAt: d.updatedAt ?? '',
        _raw: d,
    };
}

export async function getAdminDoc(uid) {
    const snap = await getDoc(doc(db, 'admins', uid));
    return snap.exists() ? mapAdminDoc(snap.id, snap.data()) : null;
}

export async function createAdminDoc(uid, data) {
    await setDoc(doc(db, 'admins', uid), {
        fullName: data.Full_Name || '',
        email: data.Email || '',
        phone: data.Phone || '',
        adminRole: data.Role || 'sub_admin',
        firstLogin: data.first_login ?? false,
        isActive: data.isActive ?? true,
        createdBy: data.createdBy || null,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    });
}

export async function updateAdminDoc(uid, fields) {
    await updateDoc(doc(db, 'admins', uid), { ...fields, updatedAt: new Date().toISOString() });
}

export async function deleteAdminDoc(uid) {
    await deleteDoc(doc(db, 'admins', uid));
}

export function watchAdmins(cb) {
    return onSnapshot(collection(db, 'admins'), (snap) => {
        const admins = snap.docs.map((a) => mapAdminDoc(a.id, a.data()));
        admins.sort((a, b) => (b.createdAt || '').localeCompare(a.createdAt || ''));
        cb(admins);
    });
}

// Staff-side notifications: docs addressed to this uid directly plus
// broadcast docs with recipientRole 'staff'.
export function watchStaffNotifications(uid, cb, limit = 20) {
    return onSnapshot(collection(db, 'notifications'), (snap) => {
        const items = snap.docs
            .map((n) => mapNotification(n.id, n.data()))
            .filter((n) => n._raw.recipientId === uid || n._raw.recipientRole === 'staff');
        items.sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
        cb(items.slice(0, limit), items.filter((n) => !n.is_read).length);
    });
}

export async function addNotification(data) {
    return addDoc(collection(db, 'notifications'), {
        recipientId: data.recipientId || null,
        recipientRole: data.recipientRole || null,
        title: data.title || '',
        message: data.message || '',
        requestId: data.requestId || null,
        isRead: false,
        dateCreated: new Date().toISOString(),
    });
}

/* ------------------------------ Profile ------------------------------ */

export async function getUserProfile(uid) {
    const snap = await getDoc(doc(db, 'users', uid));
    return snap.exists() ? { uid, ...snap.data() } : null;
}

export async function updateUserProfile(uid, fields) {
    await updateDoc(doc(db, 'users', uid), fields);
}

/* ---------------------------- Notifications --------------------------- */

function mapNotification(id, d) {
    return {
        ID: id,
        title: d.title ?? '',
        message: d.message ?? '',
        requestId: d.requestId ?? '',
        is_read: d.isRead ?? false,
        created_at: d.dateCreated ?? '',
        _raw: d,
    };
}

export function watchNotifications(uid, cb, limit = 10) {
    const q = query(collection(db, 'notifications'), where('recipientId', '==', uid));
    return onSnapshot(q, (snap) => {
        const items = snap.docs.map((n) => mapNotification(n.id, n.data()));
        items.sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
        cb(items.slice(0, limit), items.filter((n) => !n.is_read).length);
    });
}

export async function markNotificationRead(id) {
    await updateDoc(doc(db, 'notifications', id), { isRead: true });
}

export async function markAllNotificationsRead(ids) {
    await Promise.all(ids.map((id) => markNotificationRead(id)));
}

/* ---------------------------- Contact form ---------------------------- */

export async function saveContactMessage(data) {
    return addDoc(collection(db, 'contact_messages'), {
        name: data.name || '',
        email: data.email || '',
        phone: data.phone || '',
        subject: data.subject || '',
        message: data.message || '',
        dateCreated: new Date().toISOString(),
        status: 'new',
    });
}
