import crypto from 'crypto';

const SALT_ROUNDS = 10;

export function hashPassword(plain) {
  return crypto
    .pbkdf2Sync(plain, process.env.PASSWORD_SALT || 'default-salt', 100000, 64, 'sha512')
    .toString('hex');
}

export function verifyPassword(plain, hash) {
  return hashPassword(plain) === hash;
}

export function generateToken() {
  return crypto.randomBytes(32).toString('hex');
}
