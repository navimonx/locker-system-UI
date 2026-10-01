import { query, queryOne, queryInsert } from '../db.js';
import { hashPassword, verifyPassword } from '../utils/auth.js';

export async function findUserById(id) {
  return queryOne('SELECT id, studentId, firstName, lastName, role, email FROM Users WHERE id = ?', [id]);
}

export async function findUserByStudentId(studentId) {
  return queryOne('SELECT id, studentId, firstName, lastName, password, role, email FROM Users WHERE studentId = ?', [
    studentId.trim()
  ]);
}

export async function findUserByEmail(email) {
  return queryOne('SELECT id, email FROM Users WHERE email = ?', [email]);
}

export async function findUserByContact(contact) {
  return queryOne('SELECT id, contact FROM Users WHERE contact = ?', [contact]);
}

export async function createUser(data) {
  const { studentId, firstName, lastName, email, contact, course, password, role = 'student' } = data;

  const hashedPassword = hashPassword(password);

  const id = await queryInsert(
    `INSERT INTO Users (studentId, firstName, lastName, email, contact, course, password, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [studentId, firstName, lastName, email, contact, course, hashedPassword, role]
  );

  return findUserById(id);
}

export async function updateUserPassword(userId, newPassword) {
  const hashedPassword = hashPassword(newPassword);
  return query('UPDATE Users SET password = ? WHERE id = ?', [hashedPassword, userId]);
}

export async function authenticateUser(studentId, password) {
  const user = await findUserByStudentId(studentId);
  if (!user) return null;

  const isValid = verifyPassword(password, user.password);
  if (!isValid) return null;

  return {
    id: user.id,
    studentId: user.studentId,
    name: `${user.firstName} ${user.lastName}`.trim(),
    firstName: user.firstName,
    lastName: user.lastName,
    email: user.email,
    role: user.role
  };
}
