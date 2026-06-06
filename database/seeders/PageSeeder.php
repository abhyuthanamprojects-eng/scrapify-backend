<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'content' => '<h2>Privacy Policy</h2>
<p>Last updated: April 26, 2026</p>
<p>Welcome to Scrapify. We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, share, and protect your information when you use the Scrapify mobile app, website, and related services.</p>

<h3>1. Who We Are</h3>
<p>Scrapify (“we”, “our”, “us”) is the data controller for your personal data.</p>
<p>If you have any questions about this policy, contact:<br>
Email: <a href="mailto:privacy@scrapi5.com">privacy@scrapi5.com</a></p>

<h3>2. Data We Collect</h3>
<p>We may collect and process the following categories of data:</p>
<ul>
    <li><strong>Identity Data:</strong> name, username, profile details.</li>
    <li><strong>Contact Data:</strong> phone number, email address, pickup address, billing details.</li>
    <li><strong>Location Data:</strong> approximate or precise location (if enabled) to check service availability and schedule pickups.</li>
    <li><strong>Pickup/Order Data:</strong> request details, pickup history, booking status, uploaded images, notes, and preferences.</li>
    <li><strong>Device and Technical Data:</strong> IP address, device type, OS version, app version, crash logs, diagnostics.</li>
    <li><strong>Communication Data:</strong> support messages, feedback, and responses.</li>
</ul>

<h3>3. Permissions We Use</h3>
<p>Depending on your device and app flow, Scrapify may request:</p>
<ul>
    <li><strong>Camera Permission (android.permission.CAMERA):</strong> to capture photos for scrap/donation/corporate pickup requests and verification flows.</li>
    <li><strong>Photos/Storage Permission:</strong> to upload existing images from your gallery.</li>
    <li><strong>Location Permission:</strong> to detect serviceable area, improve address selection, and support pickup scheduling.</li>
    <li><strong>Notification Permission:</strong> to send booking and status updates.</li>
</ul>
<p>You can revoke permissions anytime from device settings, but some features may stop working properly.</p>

<h3>4. How We Collect Data</h3>
<p>We collect data through:</p>
<ul>
    <li><strong>Direct interactions:</strong> when you register, create pickup requests, upload photos, or contact support.</li>
    <li><strong>Automated collection:</strong> app analytics, diagnostics, and technical logs generated while using the app.</li>
    <li><strong>Service interactions:</strong> booking updates, order status changes, and account actions.</li>
</ul>

<h3>5. How We Use Your Data</h3>
<p>We use your data to:</p>
<ul>
    <li>Create and manage your account.</li>
    <li>Process scrap/donation/corporate pickup requests.</li>
    <li>Verify request details and support operations.</li>
    <li>Show pickup history and live status updates.</li>
    <li>Improve app performance, reliability, and security.</li>
    <li>Send transactional notifications (OTP, booking updates, reminders).</li>
    <li>Comply with legal and regulatory requirements.</li>
</ul>
<p>We process personal data only when there is a valid legal basis, including contract performance, legal obligations, legitimate interests, or consent (where required).</p>

<h3>6. Data Sharing</h3>
<p>We may share limited data with:</p>
<ul>
    <li><strong>Operational partners:</strong> (pickup teams, warehouse/network partners) only as needed to fulfill your request.</li>
    <li><strong>Technology providers:</strong> (hosting, analytics, notifications, support tools).</li>
    <li><strong>Legal/regulatory authorities:</strong> when required by law.</li>
</ul>
<p>We do not sell your personal data.</p>

<h3>7. Data Retention</h3>
<p>We retain personal data only as long as necessary for service delivery, legal compliance, fraud prevention, dispute handling, and record-keeping. Retention duration depends on data type and legal obligations.</p>

<h3>8. Data Security</h3>
<p>We use reasonable technical and organizational safeguards to protect your information from unauthorized access, misuse, alteration, and loss. However, no system is 100% secure.</p>

<h3>9. Your Rights</h3>
<p>Subject to applicable law, you may have rights to:</p>
<ul>
    <li>Access your personal data</li>
    <li>Correct inaccurate data</li>
    <li>Request deletion of data</li>
    <li>Restrict or object to certain processing</li>
    <li>Withdraw consent (where applicable)</li>
</ul>
<p>To exercise rights, contact: <a href="mailto:privacy@scrapi5.com">privacy@scrapi5.com</a></p>

<h3>10. Children’s Privacy</h3>
<p>Scrapify is not intended for children under 18. We do not knowingly collect personal data from children.</p>

<h3>11. International Data Transfers</h3>
<p>If data is processed outside your region, we take reasonable steps to ensure appropriate safeguards are in place.</p>

<h3>12. Changes to This Policy</h3>
<p>We may update this Privacy Policy from time to time. Updated versions will be posted with a revised “Last updated” date.</p>

<h3>13. Contact Us</h3>
<p>For privacy questions, requests, or complaints:<br>
Email: <a href="mailto:privacy@scrapi5.com">privacy@scrapi5.com</a><br>
Landline: +91 11 3574 8627<br>
Mobile: +91 98702 91813<br>
Address: E-44/3 Okhla Industrial Area Phase - 2, New Delhi - 110020</p>',
                'is_active' => true,
            ]
        );
    }
}
