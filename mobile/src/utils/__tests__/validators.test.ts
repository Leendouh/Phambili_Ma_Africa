import {
  validateEmail,
  validateSAPhone,
  validatePassword,
  validateRequired,
  validateDateNotInPast,
  validateTime,
  validateAddress,
  validateRating,
  validateCommentLength,
} from '../validators';

const formatDate = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const daysFromToday = (days: number) => {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return formatDate(d);
};

describe('validateEmail', () => {
  it('accepts a normal email', () => expect(validateEmail('a@b.co')).toBeNull());
  it('rejects empty', () => expect(validateEmail('')).toBe('Email is required'));
  it('rejects missing @', () => expect(validateEmail('abc.com')).not.toBeNull());
  it('rejects spaces', () => expect(validateEmail('a b@c.com')).not.toBeNull());
});

describe('validateSAPhone', () => {
  it.each(['+27 12 345 6789', '27123456789', '0123456789', '012 345 6789'])('accepts %s', (p) =>
    expect(validateSAPhone(p)).toBeNull()
  );
  it.each(['', 'abc', '123', '+1 555 123 4567'])('rejects "%s"', (p) =>
    expect(validateSAPhone(p)).not.toBeNull()
  );
});

describe('validatePassword', () => {
  it('accepts a strong password', () => expect(validatePassword('Test1234')).toBeNull());
  it('rejects empty', () => expect(validatePassword('')).toBe('Password is required'));
  it('rejects short passwords', () => expect(validatePassword('Ab1')).toMatch(/at least 8/));
  it('rejects letters only', () => expect(validatePassword('abcdefgh')).toMatch(/letter and one number/));
  it('rejects numbers only', () => expect(validatePassword('12345678')).toMatch(/letter and one number/));
  it('rejects very long passwords', () => expect(validatePassword('a1'.repeat(40))).toMatch(/64/));
});

describe('validateRequired', () => {
  it('rejects blank and whitespace', () => {
    expect(validateRequired('', 'Name')).toBe('Name is required');
    expect(validateRequired('   ', 'Name')).toBe('Name is required');
  });
  it('accepts text', () => expect(validateRequired('x', 'Name')).toBeNull());
});

describe('validateDateNotInPast', () => {
  it('accepts today and future dates', () => {
    expect(validateDateNotInPast(daysFromToday(0))).toBeNull();
    expect(validateDateNotInPast(daysFromToday(30))).toBeNull();
  });
  it('rejects yesterday', () =>
    expect(validateDateNotInPast(daysFromToday(-1))).toBe('Date cannot be in the past'));
  it('rejects empty', () => expect(validateDateNotInPast('')).toBe('Date is required'));
  it('rejects the garbled year-22026 input that used to slip through', () =>
    expect(validateDateNotInPast('22026-1')).toMatch(/YYYY-MM-DD/));
  it.each(['2026/10/20', '20-10-2026', 'tomorrow', '2026-1-5'])('rejects bad format %s', (d) =>
    expect(validateDateNotInPast(d)).toMatch(/YYYY-MM-DD/)
  );
  it('rejects impossible calendar dates', () => {
    expect(validateDateNotInPast('2099-02-30')).not.toBeNull();
    expect(validateDateNotInPast(`${new Date().getFullYear() + 1}-13-01`)).toMatch(/does not exist/);
  });
  it('rejects dates more than 12 months away', () =>
    expect(validateDateNotInPast(daysFromToday(400))).toMatch(/12 months/));
});

describe('validateTime', () => {
  it.each(['07:00', '09:30', '18:59'])('accepts %s', (t) => expect(validateTime(t)).toBeNull());
  it('rejects empty', () => expect(validateTime('')).toBe('Time is required'));
  it.each(['9:30', '25:00', '09:60', 'noon', '0930'])('rejects bad format %s', (t) =>
    expect(validateTime(t)).toMatch(/HH:MM/)
  );
  it.each(['03:00', '19:00', '23:30'])('rejects outside service hours %s', (t) =>
    expect(validateTime(t)).toMatch(/07:00 and 18:00/)
  );
});

describe('validateAddress', () => {
  it('accepts a full address', () => expect(validateAddress('12 Main Road, Soweto')).toBeNull());
  it('rejects empty', () => expect(validateAddress('')).toBe('Site address is required'));
  it('rejects too short', () => expect(validateAddress('Home')).toMatch(/full address/));
  it('rejects too long', () => expect(validateAddress('a'.repeat(201))).toMatch(/200/));
});

describe('validateRating', () => {
  it.each([1, 3, 5])('accepts %d', (r) => expect(validateRating(r)).toBeNull());
  it.each([0, 6, -1])('rejects %d', (r) => expect(validateRating(r)).not.toBeNull());
});

describe('validateCommentLength', () => {
  it('accepts empty and 500 chars', () => {
    expect(validateCommentLength('')).toBeNull();
    expect(validateCommentLength('a'.repeat(500))).toBeNull();
  });
  it('rejects 501 chars', () => expect(validateCommentLength('a'.repeat(501))).not.toBeNull());
});
