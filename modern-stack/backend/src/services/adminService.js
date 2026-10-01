import { query, queryOne } from '../db.js';

export async function getAllReservations() {
  return query(`
    SELECT r.id, r.user_id, r.locker_id, r.status, r.duration, r.reserved_at, r.ends_at,
           l.department, l.floor, l.slot,
           u.studentId, u.firstName, u.lastName, u.email, u.role
    FROM Reservations r
    INNER JOIN Lockers l ON r.locker_id = l.id
    INNER JOIN Users u ON r.user_id = u.id
    ORDER BY r.reserved_at DESC
  `);
}

export async function getAllStudents() {
  return query(`
    SELECT id, studentId, firstName, lastName, email, contact, course, role
    FROM Users
    ORDER BY lastName, firstName
  `);
}

export async function getAdminUsers() {
  return query(`
    SELECT id, studentId, firstName, lastName, email, role
    FROM Users
    WHERE role IN ('admin', 'superadmin', 'registrar')
    ORDER BY lastName, firstName
  `);
}

export async function getPendingReservations() {
  return query(`
    SELECT r.id, r.user_id, r.locker_id, r.status, r.reserved_at,
           l.department, l.floor, l.slot,
           u.studentId, u.firstName, u.lastName, u.email
    FROM Reservations r
    INNER JOIN Lockers l ON r.locker_id = l.id
    INNER JOIN Users u ON r.user_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.reserved_at ASC
  `);
}

export async function getLockerInventory() {
  return query(`
    SELECT l.id, l.department, l.floor, l.slot, l.status, l.owner_id,
           u.studentId, u.firstName, u.lastName
    FROM Lockers l
    LEFT JOIN Users u ON l.owner_id = u.id
    ORDER BY l.department, l.floor, l.slot
  `);
}

export async function approveReservationByAdmin(reservationId) {
  const reservation = await queryOne('SELECT * FROM Reservations WHERE id = ? AND status = "pending"', [reservationId]);
  if (!reservation) throw new Error('Pending reservation not found');

  await query('UPDATE Reservations SET status = "approved" WHERE id = ?', [reservationId]);
  await query('UPDATE Lockers SET status = "occupied" WHERE id = ?', [reservation.locker_id]);

  return reservation;
}

export async function rejectReservationByAdmin(reservationId) {
  const reservation = await queryOne('SELECT * FROM Reservations WHERE id = ? AND status = "pending"', [reservationId]);
  if (!reservation) throw new Error('Pending reservation not found');

  await query('UPDATE Reservations SET status = "rejected" WHERE id = ?', [reservationId]);
  await query('UPDATE Lockers SET status = "available", owner_id = NULL WHERE id = ?', [reservation.locker_id]);

  return reservation;
}

export async function releaseLockerByAdmin(lockerId) {
  const locker = await queryOne('SELECT * FROM Lockers WHERE id = ?', [lockerId]);
  if (!locker) throw new Error('Locker not found');

  await query('UPDATE Lockers SET status = "available", owner_id = NULL WHERE id = ?', [lockerId]);
  await query('UPDATE Reservations SET status = "released", released_at = NOW() WHERE locker_id = ? AND status = "approved"', [lockerId]);

  return locker;
}
