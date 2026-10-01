import { query, queryOne, queryInsert } from '../db.js';

export async function getUserReservations(userId) {
  return query(
    `SELECT r.id, r.user_id, r.locker_id, r.status, r.duration, r.reserved_at, r.ends_at, r.released_at,
            l.department, l.floor, l.slot
     FROM Reservations r
     INNER JOIN Lockers l ON r.locker_id = l.id
     WHERE r.user_id = ?
     ORDER BY CASE WHEN r.status IN ('pending', 'approved') THEN 0 ELSE 1 END,
              r.reserved_at DESC`,
    [userId]
  );
}

export async function getReservationById(reservationId) {
  return queryOne(
    `SELECT r.id, r.user_id, r.locker_id, r.status, r.duration, r.reserved_at, r.ends_at,
            l.department, l.floor, l.slot
     FROM Reservations r
     INNER JOIN Lockers l ON r.locker_id = l.id
     WHERE r.id = ?`,
    [reservationId]
  );
}

export async function createReservation(userId, lockerId, duration = '1 Semester') {
  const locker = await queryOne('SELECT id FROM Lockers WHERE id = ? AND status = "available"', [lockerId]);

  if (!locker) {
    throw new Error('Locker is no longer available');
  }

  const id = await queryInsert(
    `INSERT INTO Reservations (user_id, locker_id, status, duration, reserved_at)
     VALUES (?, ?, 'pending', ?, NOW())`,
    [userId, lockerId, duration]
  );

  // Update locker status to pending
  await query('UPDATE Lockers SET status = "pending", owner_id = ? WHERE id = ?', [userId, lockerId]);

  return getReservationById(id);
}

export async function updateReservationStatus(reservationId, status) {
  return query('UPDATE Reservations SET status = ? WHERE id = ?', [status, reservationId]);
}

export async function cancelReservation(reservationId, userId) {
  const reservation = await queryOne(
    'SELECT locker_id FROM Reservations WHERE id = ? AND user_id = ? AND status = "pending"',
    [reservationId, userId]
  );

  if (!reservation) {
    throw new Error('Only pending reservations can be cancelled');
  }

  await query('UPDATE Reservations SET status = "cancelled" WHERE id = ?', [reservationId]);
  await query('UPDATE Lockers SET status = "available", owner_id = NULL WHERE id = ?', [reservation.locker_id]);

  return true;
}

export async function approveReservation(reservationId) {
  const reservation = await queryOne(
    'SELECT locker_id FROM Reservations WHERE id = ? AND status = "pending"',
    [reservationId]
  );

  if (!reservation) {
    throw new Error('Reservation not found or not pending');
  }

  await query('UPDATE Reservations SET status = "approved" WHERE id = ?', [reservationId]);
  await query('UPDATE Lockers SET status = "occupied" WHERE id = ?', [reservation.locker_id]);

  return getReservationById(reservationId);
}

export async function releaseReservation(reservationId) {
  const reservation = await queryOne(
    'SELECT locker_id FROM Reservations WHERE id = ? AND status IN ("approved", "occupied")',
    [reservationId]
  );

  if (!reservation) {
    throw new Error('Reservation not found or cannot be released');
  }

  await query('UPDATE Reservations SET status = "released", released_at = NOW() WHERE id = ?', [reservationId]);
  await query('UPDATE Lockers SET status = "available", owner_id = NULL WHERE id = ?', [reservation.locker_id]);

  return getReservationById(reservationId);
}

export async function getPendingReservations() {
  return query(
    `SELECT r.id, r.user_id, r.locker_id, r.status, r.reserved_at,
            l.department, l.floor, l.slot,
            u.firstName, u.lastName, u.studentId, u.email
     FROM Reservations r
     INNER JOIN Lockers l ON r.locker_id = l.id
     INNER JOIN Users u ON r.user_id = u.id
     WHERE r.status = 'pending'
     ORDER BY r.reserved_at ASC`
  );
}
