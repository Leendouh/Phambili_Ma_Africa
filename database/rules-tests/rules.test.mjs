import { readFileSync } from 'node:fs';
import { after, before, beforeEach, describe, it } from 'node:test';
import { assertFails, assertSucceeds, initializeTestEnvironment } from '@firebase/rules-unit-testing';
import {
  addDoc,
  collection,
  deleteDoc,
  doc,
  getDoc,
  getDocs,
  query,
  setDoc,
  updateDoc,
  setLogLevel,
  where,
} from 'firebase/firestore';

setLogLevel('silent');

const [host, port] = (process.env.FIRESTORE_EMULATOR_HOST || '127.0.0.1:8085').split(':');
let env;

const as = (uid) => env.authenticatedContext(uid).firestore();
const anon = () => env.unauthenticatedContext().firestore();

const profile = (role, extra = {}) => ({
  role,
  firstName: 'Test',
  lastName: role,
  email: `${role}@test.com`,
  phone: '+27000000000',
  dateRegistered: '2026-10-01T08:00:00.000Z',
  ...extra,
});

const request = (over = {}) => ({
  refNumber: 'PMA-ABC12345',
  customerId: 'cust1',
  customerName: 'Test Customer',
  customerPhone: '+27000000000',
  serviceId: 's1',
  serviceName: 'Plumbing Repair',
  employeeId: null,
  employeeName: null,
  staffId: null,
  requestedDate: '2026-10-25',
  requestedTime: '09:00',
  siteAddress: '12 Main Road, Soweto',
  status: 'Pending',
  hasFeedback: false,
  dateCreated: '2026-10-17T10:00:00.000Z',
  ...over,
});

const notification = (over = {}) => ({
  recipientId: 'cust1',
  requestId: 'r_progress',
  message: 'Request PMA-ABC12345 is now In Progress',
  isRead: false,
  dateCreated: '2026-10-17T10:00:00.000Z',
  ...over,
});

before(async () => {
  env = await initializeTestEnvironment({
    projectId: 'demo-phambili',
    firestore: {
      rules: readFileSync(new URL('../firestore.rules', import.meta.url), 'utf8'),
      host,
      port: Number(port),
    },
  });
});

after(async () => {
  await env.cleanup();
});

beforeEach(async () => {
  await env.clearFirestore();
  await env.withSecurityRulesDisabled(async (ctx) => {
    const db = ctx.firestore();
    await setDoc(doc(db, 'users/staff1'), profile('staff'));
    await setDoc(doc(db, 'users/emp1'), profile('employee', { isAvailable: true }));
    await setDoc(doc(db, 'users/emp2'), profile('employee', { isAvailable: true }));
    await setDoc(doc(db, 'users/cust1'), profile('customer'));
    await setDoc(doc(db, 'users/cust2'), profile('customer'));
    await setDoc(doc(db, 'services/s1'), { name: 'Plumbing Repair', description: 'Fix leaks', price: 450, isActive: true });
    await setDoc(doc(db, 'requests/r_pending'), request());
    await setDoc(doc(db, 'requests/r_approved'), request({ status: 'Approved', employeeId: 'emp1', employeeName: 'Emp One', staffId: 'staff1' }));
    await setDoc(doc(db, 'requests/r_progress'), request({ status: 'In Progress', employeeId: 'emp1', employeeName: 'Emp One', staffId: 'staff1' }));
    await setDoc(doc(db, 'requests/r_done'), request({ status: 'Completed', employeeId: 'emp1', employeeName: 'Emp One', staffId: 'staff1' }));
    await setDoc(doc(db, 'requests/r_done_fb'), request({ status: 'Completed', employeeId: 'emp1', employeeName: 'Emp One', staffId: 'staff1', hasFeedback: true }));
    await setDoc(doc(db, 'feedback/r_done_fb'), { customerId: 'cust1', rating: 5, comment: 'Great', dateSubmitted: '2026-10-17T11:00:00.000Z' });
    await setDoc(doc(db, 'notifications/n1'), notification());
  });
});

describe('unauthenticated users', () => {
  it('can only browse services and products (website catalogue)', async () => {
    await assertSucceeds(getDoc(doc(anon(), 'services/s1')));
    await assertFails(getDoc(doc(anon(), 'users/cust1')));
    await assertFails(getDoc(doc(anon(), 'requests/r_pending')));
    await assertFails(getDocs(collection(anon(), 'notifications')));
  });
  it('cannot write anything', async () => {
    await assertFails(setDoc(doc(anon(), 'requests/x'), request()));
    await assertFails(setDoc(doc(anon(), 'users/hacker'), profile('customer')));
  });
});

describe('users and roles', () => {
  it('a user can read their own profile but not someone else\'s', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'users/cust1')));
    await assertFails(getDoc(doc(as('cust1'), 'users/cust2')));
    await assertFails(getDoc(doc(as('emp1'), 'users/cust1')));
  });
  it('staff can read any profile', async () => {
    await assertSucceeds(getDoc(doc(as('staff1'), 'users/cust1')));
    await assertSucceeds(getDocs(query(collection(as('staff1'), 'users'), where('role', '==', 'employee'))));
  });
  it('anyone can self-register as a customer', async () => {
    await assertSucceeds(setDoc(doc(as('newuser'), 'users/newuser'), profile('customer')));
  });
  it('nobody can self-register as staff or employee', async () => {
    await assertFails(setDoc(doc(as('newuser'), 'users/newuser'), profile('staff')));
    await assertFails(setDoc(doc(as('newuser'), 'users/newuser'), profile('employee')));
  });
  it('cannot register a profile under another user\'s id', async () => {
    await assertFails(setDoc(doc(as('newuser'), 'users/cust2'), profile('customer')));
  });
  it('a customer cannot promote themselves to staff', async () => {
    await assertFails(updateDoc(doc(as('cust1'), 'users/cust1'), { role: 'staff' }));
  });
  it('a customer can update their own non-role fields', async () => {
    await assertSucceeds(updateDoc(doc(as('cust1'), 'users/cust1'), { address: '5 New Street, Durban' }));
  });
  it('a customer cannot edit another profile', async () => {
    await assertFails(updateDoc(doc(as('cust1'), 'users/cust2'), { phone: '+27111111111' }));
  });
  it('staff can update an employee (availability)', async () => {
    await assertSucceeds(updateDoc(doc(as('staff1'), 'users/emp1'), { isAvailable: false }));
  });
  it('profiles can never be deleted', async () => {
    await assertFails(deleteDoc(doc(as('cust1'), 'users/cust1')));
    await assertFails(deleteDoc(doc(as('staff1'), 'users/cust1')));
  });
});

describe('services', () => {
  it('signed-in users can read services, customers cannot change them', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'services/s1')));
    await assertSucceeds(getDoc(doc(as('emp1'), 'services/s1')));
    await assertFails(updateDoc(doc(as('cust1'), 'services/s1'), { price: 1 }));
    await assertFails(deleteDoc(doc(as('emp1'), 'services/s1')));
  });
  it('staff can add, deactivate and delete services', async () => {
    const db = as('staff1');
    await assertSucceeds(setDoc(doc(db, 'services/s2'), { name: 'Painting', description: 'Walls', price: 350, isActive: true }));
    await assertSucceeds(updateDoc(doc(db, 'services/s2'), { isActive: false }));
    await assertSucceeds(deleteDoc(doc(db, 'services/s2')));
  });
  it('staff manage services freely (field validation is handled by the admin UI)', async () => {
    const db = as('staff1');
    await assertSucceeds(setDoc(doc(db, 'services/bad1'), { name: 'X', description: '', price: -5, isActive: true }));
    await assertSucceeds(setDoc(doc(db, 'services/bad2'), { name: '', description: '', price: 5, isActive: true }));
  });
});

describe('creating requests', () => {
  it('a customer can submit a valid request', async () => {
    await assertSucceeds(setDoc(doc(as('cust1'), 'requests/new1'), request()));
  });
  it('rejects a request that is not Pending or is already assigned', async () => {
    const db = as('cust1');
    await assertFails(setDoc(doc(db, 'requests/a'), request({ status: 'Approved' })));
    await assertFails(setDoc(doc(db, 'requests/b'), request({ employeeId: 'emp1' })));
  });
  it('rejects a request made on behalf of another customer', async () => {
    await assertFails(setDoc(doc(as('cust1'), 'requests/a'), request({ customerId: 'cust2' })));
  });
  it('accepts flexible field formats — date/time/address validation lives in the app', async () => {
    const db = as('cust1');
    await assertSucceeds(setDoc(doc(db, 'requests/a'), request({ requestedDate: '25/10/2026', requestedTime: '9:30' })));
    await assertSucceeds(setDoc(doc(db, 'requests/b'), request({ siteAddress: 'Home' })));
  });
  it('employees and staff cannot create requests', async () => {
    await assertFails(setDoc(doc(as('emp1'), 'requests/a'), request({ customerId: 'emp1' })));
    await assertFails(setDoc(doc(as('staff1'), 'requests/b'), request({ customerId: 'staff1' })));
  });
});

describe('reading requests', () => {
  it('a customer can read only their own requests', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'requests/r_pending')));
    await assertFails(getDoc(doc(as('cust2'), 'requests/r_pending')));
    await assertSucceeds(getDocs(query(collection(as('cust1'), 'requests'), where('customerId', '==', 'cust1'))));
    await assertFails(getDocs(collection(as('cust1'), 'requests')));
  });
  it('an employee can read only jobs assigned to them', async () => {
    await assertSucceeds(getDoc(doc(as('emp1'), 'requests/r_approved')));
    await assertFails(getDoc(doc(as('emp2'), 'requests/r_approved')));
    await assertFails(getDoc(doc(as('emp1'), 'requests/r_pending')));
    await assertSucceeds(getDocs(query(collection(as('emp1'), 'requests'), where('employeeId', '==', 'emp1'))));
  });
  it('staff can read every request', async () => {
    await assertSucceeds(getDocs(collection(as('staff1'), 'requests')));
  });
  it('requests can never be deleted', async () => {
    await assertFails(deleteDoc(doc(as('cust1'), 'requests/r_pending')));
    await assertFails(deleteDoc(doc(as('staff1'), 'requests/r_pending')));
  });
});

describe('status transitions: staff', () => {
  it('can approve and assign a Pending request', async () => {
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'Approved', employeeId: 'emp1', employeeName: 'Emp One' }));
  });
  it('can reject a Pending request', async () => {
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'Rejected' }));
  });
  it('can drive the full lifecycle, including website quote statuses', async () => {
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'In Progress' }));
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'Completed' }));
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'Quoted', quotedAmount: 950 }));
  });
  it('can reopen a Completed request', async () => {
    await assertSucceeds(updateDoc(doc(as('staff1'), 'requests/r_done'), { status: 'Approved' }));
  });
  it('cannot reassign a request to a different customer', async () => {
    await assertFails(updateDoc(doc(as('staff1'), 'requests/r_pending'), { status: 'Approved', customerId: 'cust2' }));
  });
});

describe('status transitions: customers', () => {
  it('cannot approve their own request or change its status', async () => {
    await assertFails(updateDoc(doc(as('cust1'), 'requests/r_pending'), { status: 'Approved' }));
    await assertFails(updateDoc(doc(as('cust1'), 'requests/r_approved'), { status: 'Completed' }));
  });
  it('can edit date/time/address while awaiting a quote', async () => {
    await assertSucceeds(updateDoc(doc(as('cust1'), 'requests/r_pending'), { siteAddress: '99 Other Road, Cape Town' }));
  });
  it('can cancel their own request or confirm a quote', async () => {
    await assertSucceeds(updateDoc(doc(as('cust1'), 'requests/r_pending'), { status: 'Cancelled' }));
    await assertFails(updateDoc(doc(as('cust2'), 'requests/r_pending'), { status: 'Cancelled' }));
    await assertFails(updateDoc(doc(as('cust1'), 'requests/r_done'), { status: 'Cancelled' }));
  });
});

describe('status transitions: employees', () => {
  it('can start an Approved job assigned to them', async () => {
    await assertSucceeds(updateDoc(doc(as('emp1'), 'requests/r_approved'), { status: 'In Progress' }));
  });
  it('can complete an In Progress job assigned to them', async () => {
    await assertSucceeds(updateDoc(doc(as('emp1'), 'requests/r_progress'), { status: 'Completed' }));
  });
  it('cannot skip a step (Approved straight to Completed)', async () => {
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_approved'), { status: 'Completed' }));
  });
  it('cannot start a Pending (unapproved) request, even if assigned', async () => {
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_pending'), { status: 'In Progress' }));
  });
  it('cannot move a Completed job backwards', async () => {
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_done'), { status: 'In Progress' }));
  });
  it('cannot touch a job assigned to someone else', async () => {
    await assertFails(updateDoc(doc(as('emp2'), 'requests/r_approved'), { status: 'In Progress' }));
  });
  it('cannot approve or reject requests', async () => {
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_pending'), { status: 'Approved' }));
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_pending'), { status: 'Rejected' }));
  });
  it('can only change the status field, nothing else', async () => {
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_approved'), { status: 'In Progress', siteAddress: '1 Elsewhere Road' }));
    await assertFails(updateDoc(doc(as('emp1'), 'requests/r_approved'), { status: 'In Progress', employeeId: 'emp2' }));
  });
});

describe('feedback', () => {
  const feedback = (over = {}) => ({ customerId: 'cust1', rating: 4, comment: 'Good work', dateSubmitted: '2026-10-17T12:00:00.000Z', ...over });

  it('a customer can rate their own Completed request', async () => {
    await assertSucceeds(setDoc(doc(as('cust1'), 'feedback/r_done'), feedback()));
    await assertSucceeds(updateDoc(doc(as('cust1'), 'requests/r_done'), { hasFeedback: true }));
  });
  it('feedback is only allowed once the request is Completed', async () => {
    await assertFails(setDoc(doc(as('cust1'), 'feedback/r_pending'), feedback()));
    await assertFails(setDoc(doc(as('cust1'), 'feedback/r_approved'), feedback()));
    await assertFails(setDoc(doc(as('cust1'), 'feedback/r_progress'), feedback()));
  });
  it('only one feedback per request (second attempt and edits are blocked)', async () => {
    await assertFails(setDoc(doc(as('cust1'), 'feedback/r_done_fb'), feedback({ rating: 1 })));
    await assertFails(updateDoc(doc(as('cust1'), 'feedback/r_done_fb'), { rating: 1 }));
  });
  it('rating must be a whole number from 1 to 5', async () => {
    const db = as('cust1');
    await assertFails(setDoc(doc(db, 'feedback/r_done'), feedback({ rating: 0 })));
    await assertFails(setDoc(doc(db, 'feedback/r_done'), feedback({ rating: 6 })));
    await assertFails(setDoc(doc(db, 'feedback/r_done'), feedback({ rating: 3.5 })));
    await assertFails(setDoc(doc(db, 'feedback/r_done'), feedback({ rating: '5' })));
  });
  it('comment is limited to 500 characters', async () => {
    await assertSucceeds(setDoc(doc(as('cust1'), 'feedback/r_done'), feedback({ comment: 'a'.repeat(500) })));
  });
  it('a 501 character comment is rejected', async () => {
    await assertFails(setDoc(doc(as('cust1'), 'feedback/r_done'), feedback({ comment: 'a'.repeat(501) })));
  });
  it('another customer cannot rate someone else\'s request', async () => {
    await assertFails(setDoc(doc(as('cust2'), 'feedback/r_done'), feedback({ customerId: 'cust2' })));
    await assertFails(setDoc(doc(as('cust2'), 'feedback/r_done'), feedback({ customerId: 'cust1' })));
  });
  it('employees and staff cannot leave feedback', async () => {
    await assertFails(setDoc(doc(as('emp1'), 'feedback/r_done'), feedback({ customerId: 'emp1' })));
    await assertFails(setDoc(doc(as('staff1'), 'feedback/r_done'), feedback({ customerId: 'staff1' })));
  });
  it('a customer can only flip the feedback flag, not other fields', async () => {
    await assertFails(updateDoc(doc(as('cust1'), 'requests/r_approved'), { hasFeedback: true, siteAddress: 'X' }));
    await assertFails(updateDoc(doc(as('cust2'), 'requests/r_done'), { hasFeedback: true }));
  });
  it('a customer can look up feedback that does not exist yet (no error)', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'feedback/r_done')));
  });
  it('reading: owner and staff yes, other customers no', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'feedback/r_done_fb')));
    await assertSucceeds(getDoc(doc(as('staff1'), 'feedback/r_done_fb')));
    await assertFails(getDoc(doc(as('cust2'), 'feedback/r_done_fb')));
    await assertSucceeds(getDocs(collection(as('staff1'), 'feedback')));
    await assertFails(getDocs(collection(as('cust1'), 'feedback')));
  });
  it('feedback can never be deleted', async () => {
    await assertFails(deleteDoc(doc(as('cust1'), 'feedback/r_done_fb')));
    await assertFails(deleteDoc(doc(as('staff1'), 'feedback/r_done_fb')));
  });
});

describe('notifications', () => {
  it('staff can notify a customer or an employee', async () => {
    const db = as('staff1');
    await assertSucceeds(addDoc(collection(db, 'notifications'), notification()));
    await assertSucceeds(addDoc(collection(db, 'notifications'), notification({ recipientId: 'emp1', requestId: 'r_approved', message: 'You have been assigned job PMA-ABC12345' })));
  });
  it('an employee can notify the customer of their own job', async () => {
    await assertSucceeds(addDoc(collection(as('emp1'), 'notifications'), notification()));
  });
  it('any signed-in user can create notifications (shared staff feed)', async () => {
    await assertSucceeds(addDoc(collection(as('cust1'), 'notifications'), notification({ recipientId: 'cust2' })));
    await assertSucceeds(addDoc(collection(as('emp2'), 'notifications'), notification()));
  });
  it('only the recipient and staff can read a notification', async () => {
    await assertSucceeds(getDoc(doc(as('cust1'), 'notifications/n1')));
    await assertFails(getDoc(doc(as('cust2'), 'notifications/n1')));
    await assertSucceeds(getDoc(doc(as('staff1'), 'notifications/n1')));
    await assertSucceeds(getDocs(query(collection(as('cust1'), 'notifications'), where('recipientId', '==', 'cust1'))));
  });
  it('the recipient can mark as read but cannot edit the message', async () => {
    await assertSucceeds(updateDoc(doc(as('cust1'), 'notifications/n1'), { isRead: true }));
    await assertFails(updateDoc(doc(as('cust1'), 'notifications/n1'), { message: 'tampered' }));
    await assertFails(updateDoc(doc(as('cust2'), 'notifications/n1'), { isRead: true }));
  });
  it('notifications can never be deleted', async () => {
    await assertFails(deleteDoc(doc(as('cust1'), 'notifications/n1')));
  });
});
