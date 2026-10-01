import { useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes, useParams } from 'react-router-dom';

const STORAGE_KEY = 'securelocker_user';

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
          {user ? <Link to="/dashboard">Dashboard</Link> : <Link to="/login">Login</Link>}
          {user ? <Link to="/my-locker">My Locker</Link> : <Link to="/signup">Sign Up</Link>}
          {user && user.role === 'admin' ? <Link to="/admin">Admin</Link> : null}
          {user ? <button className="text-button" onClick={onLogout}>Logout</button> : null}
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
          <span className="highlight"> Simplified for PLV</span>
        </h1>
        <p>
          Reserve your campus locker in seconds. View availability by building and floor,
          track your reservation in real time, and manage everything from one place.
        </p>

        <div className="cta-row">
          <Link className="btn-primary" to={user ? '/rentals' : '/signup'}>
            {user ? '🔍 Browse Lockers' : '✨ Get Started Free'}
          </Link>
          <Link className="btn-secondary" to={user ? '/my-locker' : '/login'}>
            {user ? 'My Locker →' : 'Login →'}
          </Link>
        </div>

        <div className="stats-strip">
          <div className="stat-card"><strong>6+</strong><span>Colleges</span></div>
          <div className="stat-card"><strong>6F</strong><span>Floors Each</span></div>
          <div className="stat-card"><strong>24/7</strong><span>Online Access</span></div>
          <div className="stat-card"><strong>100%</strong><span>Digital</span></div>
        </div>
      </section>

      <section className="feature-grid">
        <article className="feature-card">
          <div className="feature-icon">🗺️</div>
          <h3>Browse by College &amp; Floor</h3>
          <p>Navigate locker availability across all colleges and floors with a clean layout.</p>
        </article>
        <article className="feature-card">
          <div className="feature-icon">⚡</div>
          <h3>Instant Reservations</h3>
          <p>Reserve available lockers quickly from the browser with a streamlined intake flow.</p>
        </article>
        <article className="feature-card">
          <div className="feature-icon">📋</div>
          <h3>Digital Receipts</h3>
          <p>Track and review reservation records from the student or admin dashboard.</p>
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
      const response = await fetch('http://localhost:4000/api/auth/login', {
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
      setError(err.message || 'Unable to log in');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthLayout title="Welcome Back" subtitle="Sign in with your student ID and password to access your locker.">
      <form onSubmit={handleSubmit} className="stack-form">
        {error && <div className="alert error">{error}</div>}
        <div className="field-group">
          <label htmlFor="studentId">Student ID</label>
          <input id="studentId" value={form.studentId} onChange={(e) => setForm({ ...form, studentId: e.target.value })} required />
        </div>
        <div className="field-group">
          <label htmlFor="password">Password</label>
          <input id="password" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required />
        </div>
        <button type="submit" className="btn-primary full" disabled={loading}>{loading ? 'Logging in...' : 'Login'}</button>
      </form>
      <div className="divider">Don&apos;t have an account? <Link to="/signup">Sign up</Link></div>
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
      const response = await fetch('http://localhost:4000/api/auth/signup', {
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
      setError(err.message || 'Unable to create account');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthLayout title="Create Account" subtitle="Your Student ID must be on the admin-approved list before you can sign up.">
      <form onSubmit={handleSubmit} className="stack-form">
        {error && <div className="alert error">{error}</div>}
        <div className="two-col">
          <div className="field-group"><label>First Name</label><input value={form.firstName} onChange={(e) => setForm({ ...form, firstName: e.target.value })} required /></div>
          <div className="field-group"><label>Last Name</label><input value={form.lastName} onChange={(e) => setForm({ ...form, lastName: e.target.value })} required /></div>
        </div>
        <div className="field-group"><label>Student Number</label><input value={form.studentId} onChange={(e) => setForm({ ...form, studentId: e.target.value })} placeholder="22-1234" required /></div>
        <div className="two-col">
          <div className="field-group"><label>Contact</label><input value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} required /></div>
          <div className="field-group"><label>Email</label><input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required /></div>
        </div>
        <div className="field-group"><label>Password</label><input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required /></div>
        <div className="field-group"><label>Confirm Password</label><input type="password" value={form.confirmPassword} onChange={(e) => setForm({ ...form, confirmPassword: e.target.value })} required /></div>
        <button type="submit" className="btn-primary full" disabled={loading}>{loading ? 'Creating account...' : 'Create Account'}</button>
      </form>
      <div className="divider">Already have an account? <Link to="/login">Log in</Link></div>
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

  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={() => localStorage.removeItem(STORAGE_KEY)}>
      <div className="page-header">
        <div className="eyebrow">{selected.icon} {selected.label}</div>
        <h1>{selected.label} Locker Availability</h1>
      </div>

      <div className="floor-grid">
        {[1, 2, 3, 4, 5, 6].map((floor) => (
          <div key={floor} className="floor-card">
            <div className="floor-title">Floor {floor}</div>
            <div className="locker-row">
              {Array.from({ length: 5 }, (_, i) => (
                <span key={i} className={`locker-box ${i % 2 === 0 ? 'free' : 'pending'}`}>{i + 1}</span>
              ))}
            </div>
          </div>
        ))}
      </div>
    </AppShell>
  );
}

function DashboardPage({ user, onLogout }) {
  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">📋 Student Dashboard</div>
        <h1>Welcome, {user.name || 'Student'}</h1>
        <p>Reserve a locker, track your booking, and keep your account status up to date.</p>
        <div className="cta-row">
          <Link to="/rentals" className="btn-primary">Browse Lockers</Link>
          <Link to="/my-locker" className="btn-secondary">My Locker</Link>
        </div>
      </div>
    </AppShell>
  );
}

function MyLockerPage({ user, onLogout }) {
  if (!user) return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">📦 My Locker</div>
        <h1>Current Reservation</h1>
        <div className="reservation-box">
          <div className="row"><span>Locker</span><strong>L-06 / CEIT / Floor 3</strong></div>
          <div className="row"><span>Status</span><strong>Approved</strong></div>
          <div className="row"><span>Duration</span><strong>1 Semester</strong></div>
          <div className="row"><span>Ends</span><strong>July 2027</strong></div>
        </div>
      </div>
    </AppShell>
  );
}

function AdminPage({ user, onLogout }) {
  if (!user || user.role !== 'admin') return <Navigate to="/login" replace />;

  return (
    <AppShell user={user} onLogout={onLogout}>
      <div className="panel-box">
        <div className="eyebrow">🛠️ Admin Dashboard</div>
        <h1>Welcome, Admin</h1>
        <p>Manage lockers, approve pending reservations, and release occupied lockers across all colleges.</p>
        <div className="cta-row">
          <Link to="/admin/records" className="btn-primary">View Records</Link>
          <Link to="/admin/lockers" className="btn-secondary">Manage Lockers</Link>
        </div>
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
      <Route path="/admin" element={<AdminPage user={user} onLogout={handleLogout} />} />
    </Routes>
  );
}
