import { useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes, useParams } from 'react-router-dom';

const STORAGE_KEY = 'securelocker_user';
const API_URL = 'http://localhost:4000/api';

const departments = [
  { key: 'CABA', label: 'CABA', icon: '🏢' },
  { key: 'CEIT', label: 'CEIT', icon: '💻' },
  { key: 'COED', label: 'COED', icon: '📚' },
  { key: 'CPAG', label: 'CPAG', icon: '⚖️' },
  { key: 'NB', label: 'NB', icon: '🔬' },
  { key: 'CAS', label: 'CAS', icon: '🎭' }
];

function getStoredUser() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function AppShell({ user, onLogout, children }) {
  return (
    <div className="page-shell">
      <header className="topbar">
        <div className="brand">
          <span className="brand-icon">🔒</span>
          <span>SecureLocker</span>
        </div>

        <nav className="nav">
          <Link to="/">Home</Link>
          {user ? (
            <>
              <Link to="/rentals">Rentals</Link>
              <Link to="/my-locker">My Locker</Link>
              {user.role === 'admin' && <Link to="/admin">Admin</Link>}
              <button className="text-button" onClick={onLogout}>Logout</button>
            </>
          ) : (
            <>
              <Link to="/login">Login</Link>
              <Link to="/signup">Sign Up</Link>
            </>
          )}
        </nav>
      </header>
      {children}
    </div>
  );
}

function HomePage({ user }) {
  return (
    <AppShell user={user} onLogout={() => {}}>
      <section className="hero">
        <div className="eyebrow">🎓 Pamantasan ng Lungsod ng Valenzuela</div>
        <h1>
          Smart Locker Reservations
          <span className="highlight"> Made Simple</span>
        </h1>
        <p>
          Reserve your campus locker in seconds. Browse by building and floor, track your
          reservation in real time, and manage everything from one dashboard.
        </p>

        <div className="cta-row">
          <Link className="btn-primary" to={user ? '/rentals' : '/signup'}>
            {user ? '🔍 Browse Lockers' : '✨ Get Started'}
          </Link>
          <Link className="btn-secondary" to={user ? '/my-locker' : '/login'}>
            {user ? 'My Locker →' : 'Login →'}
          </Link>
        </div>

        <div className="stats-strip">
          <div className="stat-card"><strong>6+</strong><span>Colleges</span></div>
          <div className="stat-card"><strong>6F</strong><span>Floors Each</span></div>
          <div className="stat-card"><strong>24/7</strong><span>Online</span></div>
          <div className="stat-card"><strong>100%</strong><span>Digital</span></div>
        </div>
      </section>

      <section className="feature-grid">
        <article className="feature-card">
          <div className="feature-icon">🗺️</div>
          <h3>Browse by Floor</h3>
          <p>Navigate across all colleges and floors to see real-time locker availability.</p>
        </article>
        <article className="feature-card">
          <div className="feature-icon">⚡</div>
          <h3>Instant Reserve</h3>
          <p>Book your locker in seconds with our streamlined reservation system.</p>
        </article>
        <article className="feature-card">
          <div className="feature-icon">📋</div>
          <h3>Digital Receipts</h3>
          <p>Keep digital records and track all your reservations in one place.</p>
        </article>
      </section>
    </AppShell>
  );
}

function AuthLayout({ title, subtitle, children }) {
  return (
    <main className="auth-layout">
      <div className="auth-panel">
        <div className="auth-card">
          <h2>{title}</h2>
          <p className="subtitle">{subtitle}</p>
          {children}
        </div>
      </div>
      <div className="auth-panel auth-panel--visual" />
    </main>
  );
}

function LoginPage({ setUser }) {
  const [form, setForm] = useState({ studentId: '', password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form)
      });

      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Login failed');

      localStorage.setItem(STORAGE_KEY, JSON.stringify(data.user));
      setUser(data.user);
      window.location.href = data.user.role === 'admin' ? '/admin' : '/dashboard';
    } catch (err) {
      setError(err.message || 'Unable to log in. Check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthLayout title="Welcome Back" subtitle="Sign in with your student ID and password.">
      <form onSubmit={handleSubmit} className="stack-form">
        {error && <div className="alert error">❌ {error}</div>}
        <div className="field-group">
          <label htmlFor="studentId">Student ID</label>
          <input
            id="studentId"
            value={form.studentId}
            onChange={(e) => setForm({ ...form, studentId: e.target.value })}
            placeholder="e.g. 22-1234"
            required
            autoFocus
          />
        </div>
        <div className="field-group">
          <label htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
            required
          />
        </div>
        <button type="submit" className="btn-primary full" disabled={loading}>
          {loading ? 'Logging in...' : 'Login'}
        </button>
      </form>
      <div className="divider">
        Don't have an account? <Link to="/signup">Sign up</Link>
      </div>
    </AuthLayout>
  );
}

function SignupPage({ setUser }) {
  const [form, setForm] = useState({
    firstName: '',
    lastName: '',
    studentId: '',
    contact: '',
    email: '',
    password: '',
    confirmPassword: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await fetch(`${API_URL}/auth/signup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form)
      });

      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Signup failed');

      localStorage.setItem(STORAGE_KEY, JSON.stringify(data.user));
      setUser(data.user);
      window.location.href = '/dashboard';
    } catch (err) {
      setError(err.message || 'Unable to create account.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthLayout title="Create Account" subtitle="Your Student ID must be on the admin-approved list.">
      <form onSubmit={handleSubmit} className="stack-form">
        {error && <div className="alert error">❌ {error}</div>}
        <div className="two-col">
          <div className="field-group">
            <label>First Name</label>
            <input value={form.firstName} onChange={(e) => setForm({ ...form, firstName: e.target.value })} required />
          </div>
          <div className="field-group">
            <label>Last Name</label>
            <input value={form.lastName} onChange={(e) => setForm({ ...form, lastName: e.target.value })} required />
          </div>
        </div>
        <div className="field-group">
          <label>Student Number</label>
          <input value={form.studentId} onChange={(e) => setForm({ ...form, studentId: e.target.value })} placeholder="22-1234" required />
        </div>
        <div className="two-col">
          <div className="field-group">
            <label>Contact Number</label>
            <input value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} required />
          </div>
          <div className="field-group">
            <label>Email</label>
            <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
          </div>
        </div>
        <div className="field-group">
          <label>Password</label>
          <input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required />
        </div>
        <div className="field-group">
          <label>Confirm Password</label>
          <input type="password" value={form.confirmPassword} onChange={(e) => setForm({ ...form, confirmPassword: e.target.value })} required />
        </div>
        <button type="submit" className="btn-primary full" disabled={loading}>
          {loading ? 'Creating...' : 'Create Account'}
        </button>
      </form>
      <div className="divider">
        Already have an account? <Link to="/login">Log in</Link>
      </div>
    </AuthLayout>
  );
}

function RentalsPage({ user }) {
  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={() => localStorage.removeItem(STORAGE_KEY)}>
      <div className="page-header">
        <div className="eyebrow">🔍 Browse Lockers</div>
        <h1>Choose Your College</h1>
        <p>Select your college or building to view floor and locker availability.</p>
      </div>

      <div className="user-pill">
        <span className="dot"></span>
        Logged in as <strong>{user.name}</strong> · {user.role === 'admin' ? 'Admin' : 'Student'}
      </div>

      <div className="dept-grid">
        {departments.map((dept) => (
          <Link key={dept.key} to={`/rentals/${dept.key}`} className="dept-card">
            <div className="dept-icon">{dept.icon}</div>
            <div className="dept-abbr">{dept.label}</div>
            <div className="dept-arrow">View Floors →</div>
          </Link>
        ))}
      </div>
    </AppShell>
  );
}

function DepartmentPage({ user }) {
  const { dept } = useParams();
  const selected = departments.find((d) => d.key === dept) || departments[0];
  const [lockers, setLockers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchLockers() {
      try {
        const res = await fetch(`${API_URL}/lockers/${dept}`);
        const data = await res.json();
        setLockers(data);
      } catch (err) {
        console.error('Failed to fetch lockers:', err);
      } finally {
        setLoading(false);
      }
    }
    fetchLockers();
  }, [dept]);

  if (!user) return <Navigate to="/login" replace />;

  const floorsData = {};
  lockers.forEach((locker) => {
    if (!floorsData[locker.floor]) floorsData[locker.floor] = [];
    floorsData[locker.floor].push(locker);
  });

  return (
    <AppShell user={user} onLogout={() => localStorage.removeItem(STORAGE_KEY)}>
      <div className="page-header">
        <div className="eyebrow">{selected.icon} {selected.label}</div>
        <h1>{selected.label} Locker Availability</h1>
      </div>

      {loading ? (
        <div className="loading">Loading lockers...</div>
      ) : (
        <div className="floor-grid">
          {Object.entries(floorsData)
            .sort(([a], [b]) => a - b)
            .map(([floor, floorLockers]) => (
              <div key={floor} className="floor-card">
                <div className="floor-title">Floor {floor}</div>
                <div className="locker-row">
                  {floorLockers.map((locker) => (
                    <div
                      key={locker.id}
                      className={`locker-box locker-${locker.status}`}
                      title={`Locker ${locker.slot} - ${locker.status}`}
                    >
                      {locker.slot}
                    </div>
                  ))}
                </div>
              </div>
            ))}
        </div>
      )}
    </AppShell>
  );
}

function DashboardPage({ user, onLogout }) {
  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">📋 Dashboard</div>
        <h1>Welcome, {user.name}</h1>
        <p>Manage your locker reservation, track your booking status, and browse available lockers.</p>

        <div className="user-pill" style={{ marginTop: '24px', marginBottom: '28px' }}>
          <span className="dot"></span>
          Logged in as <strong>{user.name}</strong>
        </div>

        <div className="cta-row">
          <Link to="/rentals" className="btn-primary">🔍 Browse Lockers</Link>
          <Link to="/my-locker" className="btn-secondary">📦 My Locker</Link>
        </div>
      </div>
    </AppShell>
  );
}

function MyLockerPage({ user, onLogout }) {
  const [reservations, setReservations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchReservations() {
      try {
        const res = await fetch(`${API_URL}/reservations/user/${user.id}`);
        const data = await res.json();
        setReservations(data);
      } catch (err) {
        setError('Failed to load reservations');
      } finally {
        setLoading(false);
      }
    }
    fetchReservations();
  }, [user.id]);

  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">📦 My Locker</div>
        <h1>My Reservations</h1>
        <p>View active and past locker reservations.</p>

        {error && <div className="alert error">❌ {error}</div>}

        {loading ? (
          <p style={{ marginTop: '20px' }}>Loading reservations...</p>
        ) : reservations.length === 0 ? (
          <div style={{ marginTop: '24px' }}>
            <p style={{ color: 'rgba(255,255,255,0.6)' }}>You have no reservations yet.</p>
            <Link to="/rentals" className="btn-primary" style={{ marginTop: '16px', display: 'inline-flex' }}>
              🔍 Browse Lockers
            </Link>
          </div>
        ) : (
          <div className="reservation-list" style={{ marginTop: '24px' }}>
            {reservations.map((res) => (
              <div key={res.id} className="reservation-item">
                <div className="res-header">
                  <div className="res-locker">L{res.locker_id}</div>
                  <span className={`badge badge-${res.status}`}>{res.status}</span>
                </div>
                <div className="res-details">
                  <div className="res-row">
                    <span>Department</span>
                    <strong>{res.department}</strong>
                  </div>
                  <div className="res-row">
                    <span>Floor</span>
                    <strong>{res.floor}</strong>
                  </div>
                  <div className="res-row">
                    <span>Duration</span>
                    <strong>{res.duration}</strong>
                  </div>
                  <div className="res-row">
                    <span>Reserved</span>
                    <strong>{new Date(res.reserved_at).toLocaleDateString()}</strong>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AppShell>
  );
}

function AdminDashboard({ user, onLogout }) {
  if (!user || user.role !== 'admin') return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">🛠️ Admin Dashboard</div>
        <h1>Welcome, Admin</h1>
        <p>Manage lockers, approve pending reservations, and release occupied lockers across all colleges.</p>

        <div className="user-pill" style={{ marginTop: '24px', marginBottom: '28px' }}>
          <span className="dot"></span>
          Logged in as <strong>{user.name}</strong>
        </div>

        <div className="cta-row">
          <Link to="/admin/records" className="btn-primary">📊 View Records</Link>
          <Link to="/admin/pending" className="btn-secondary">⏳ Pending Approvals</Link>
          <Link to="/admin/lockers" className="btn-secondary">🔑 Manage Lockers</Link>
        </div>
      </div>
    </AppShell>
  );
}

function AdminRecordsPage({ user, onLogout }) {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchRecords() {
      try {
        const res = await fetch(`${API_URL}/admin/records`, {
          headers: { 'x-user-role': user.role }
        });
        const data = await res.json();
        setRecords(data.reservations || []);
      } catch (err) {
        setError('Failed to load records');
      } finally {
        setLoading(false);
      }
    }
    fetchRecords();
  }, [user.role]);

  if (!user || user.role !== 'admin') return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">📊 All Records</div>
        <h1>Reservation Records</h1>

        {error && <div className="alert error">❌ {error}</div>}

        {loading ? (
          <p style={{ marginTop: '20px' }}>Loading records...</p>
        ) : (
          <div className="table-wrap" style={{ marginTop: '24px' }}>
            <table className="data-table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>ID</th>
                  <th>Department</th>
                  <th>Floor</th>
                  <th>Locker</th>
                  <th>Status</th>
                  <th>Reserved</th>
                </tr>
              </thead>
              <tbody>
                {records.map((rec) => (
                  <tr key={rec.id} className={`row-${rec.status}`}>
                    <td><strong>{rec.firstName} {rec.lastName}</strong></td>
                    <td>{rec.studentId}</td>
                    <td>{rec.department}</td>
                    <td>Floor {rec.floor}</td>
                    <td>L{rec.locker_id}</td>
                    <td><span className={`badge badge-${rec.status}`}>{rec.status}</span></td>
                    <td>{new Date(rec.reserved_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AppShell>
  );
}

export default function App() {
  const [user, setUser] = useState(() => getStoredUser());

  const handleLogout = () => {
    localStorage.removeItem(STORAGE_KEY);
    setUser(null);
    window.location.href = '/';
  };

  return (
    <Routes>
      <Route path="/" element={<HomePage user={user} />} />
      <Route path="/login" element={<LoginPage setUser={setUser} />} />
      <Route path="/signup" element={<SignupPage setUser={setUser} />} />
      <Route path="/rentals" element={<RentalsPage user={user} />} />
      <Route path="/rentals/:dept" element={<DepartmentPage user={user} />} />
      <Route path="/dashboard" element={<DashboardPage user={user} onLogout={handleLogout} />} />
      <Route path="/my-locker" element={<MyLockerPage user={user} onLogout={handleLogout} />} />
      <Route path="/admin" element={<AdminDashboard user={user} onLogout={handleLogout} />} />
      <Route path="/admin/records" element={<AdminRecordsPage user={user} onLogout={handleLogout} />} />
    </Routes>
  );
}
