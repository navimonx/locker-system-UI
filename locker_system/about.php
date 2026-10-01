<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';

$pageTitle  = 'About — SecureLocker Inc.';
$activePage = 'about';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main">
  <div class="page-header">
    <div class="page-eyebrow">📖 &nbsp;About Us</div>
    <h1>SecureLocker Inc.</h1>
    <p>Smart campus locker reservations for Pamantasan ng Lungsod ng Valenzuela.</p>
  </div>

  <div class="content-box content-box--wide content-box--left">
    <p style="margin-bottom:16px;">
      SecureLocker Inc. is a technology-driven company focused on providing innovative locker reservation and management solutions for educational institutions. Our goal is to improve convenience, accessibility, and security by offering a digital platform that streamlines locker allocation and monitoring processes.
    </p>
    <p style="margin-bottom:16px;">
      We believe that students deserve a secure and efficient storage system that supports their daily academic activities. Through our user-friendly platform, students can easily reserve lockers based on building and floor availability, while administrators can efficiently manage locker records, reservations, and occupancy status.
    </p>
    <p>
      SecureLocker Inc. is committed to delivering reliable and modern solutions that enhance campus organization and promote a better student experience through technology and innovation.
    </p>

    <div class="about-grid">
      <div class="about-block">
        <h3>Mission</h3>
        <p>Our mission is to provide students and educational institutions with a secure, reliable, and user-friendly locker reservation system that improves campus organization, accessibility, and convenience through modern technology.</p>
      </div>
      <div class="about-block">
        <h3>Vision</h3>
        <p>Our vision is to become a leading provider of smart campus storage solutions by creating innovative systems that enhance efficiency, security, and student experience in educational institutions.</p>
      </div>
    </div>

    <div class="detail-section">
      <h3>Contact Us</h3>
      <div class="contact-grid">
        <div class="contact-item"><strong>Email</strong> securelockerinc@gmail.com</div>
        <div class="contact-item"><strong>Phone</strong> 8352-7000</div>
        <div class="contact-item"><strong>Location</strong> Maysan, Tongco, Valenzuela City</div>
        <div class="contact-item"><strong>Hours</strong> Mon–Fri · 8:00 AM – 5:00 PM</div>
      </div>
      <p style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.50);">
        We are committed to providing fast and reliable assistance to ensure a smooth and convenient locker reservation experience for all users.
      </p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
