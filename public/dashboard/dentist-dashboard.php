<?php
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';

// Only dentists, staff and admins may access this page
if (!isLoggedIn() || (getSessionRole() !== ROLE_DENTIST && getSessionRole() !== ROLE_STAFF && getSessionRole() !== ROLE_ADMIN)) {
    header('Location: ../auth/login.php');
    exit();
}

$userName = getSessionName();
$userBranchId = getSessionBranchId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dentist Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Minimal layout adapted from staff dashboard for dentist-specific view */
        .dentist-container{max-width:1200px;margin:24px auto;padding:16px}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
        .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:18px}
        .card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
        .card h4{margin:0 0 8px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
        .actions{display:flex;gap:8px}
        .btn{padding:8px 10px;border-radius:6px;border:none;cursor:pointer}
        .btn-primary{background:#054A91;color:#fff}
        .btn-success{background:#10b981;color:#fff}
        .btn-danger{background:#ef4444;color:#fff}
        .tab-nav{display:flex;margin-bottom:20px;border-bottom:2px solid #eee}
        .tab-nav button{padding:12px 20px;border:none;background:none;cursor:pointer;border-bottom:3px solid transparent;margin-right:10px}
        .tab-nav button.active{background:#054A91;color:#fff;border-radius:8px 8px 0 0}
        .tab-content{display:none}
        .tab-content.active{display:block}
        .prescription-form{background:#f9f9f9;padding:20px;border-radius:8px;margin:15px 0}
        .medication-item{background:#fff;padding:15px;border:1px solid #ddd;border-radius:6px;margin:10px 0}
        .form-group{margin:10px 0}
        .form-group label{display:block;margin-bottom:5px;font-weight:bold}
        .form-group input, .form-group select, .form-group textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px}
        .staff-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}
        .staff-card{background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #eee}
        .staff-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}
        .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000}
        .modal.show{display:flex;align-items:center;justify-content:center}
        .modal-content{background:#fff;padding:30px;border-radius:10px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto}
    </style>
</head>
<body>
    <div class="dentist-container">
        <div class="top">
            <div>
                <h2>🦷 Dentist Dashboard</h2>
                <div style="color:#666">Welcome, <?php echo htmlspecialchars($userName); ?> — <?php echo htmlspecialchars(getBranchName($userBranchId)); ?></div>
            </div>
            <div>
                <button class="btn" onclick="logout()">Logout</button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('analytics')">📊 Analytics</button>
            <button class="tab-btn" onclick="showTab('prescriptions')">💊 Prescriptions</button>
            <button class="tab-btn" onclick="showTab('staff')">👥 Staff Management</button>
            <button class="tab-btn" onclick="showTab('appointments')">📅 Appointments</button>
            <button class="tab-btn" onclick="showTab('credentials')">🏥 Credentials</button>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics-tab" class="tab-content active">
            <!-- Summary Cards -->
            <div class="cards">
                <div class="card">
                    <h4>Branch Revenue (This Month)</h4>
                    <div id="branch-revenue-month" style="font-size:1.6rem;font-weight:700">Loading...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Total branch revenue this month</div>
                </div>
                <div class="card">
                    <h4>Total Patients</h4>
                    <div id="total-patients" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Active patients in branch</div>
                </div>
                <div class="card">
                    <h4>Today's Appointments</h4>
                    <div id="appointments-today" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Pending / Approved / Completed</div>
                </div>
                <div class="card">
                    <h4>Pending Approvals</h4>
                    <div id="pending-count" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Appointments awaiting approval</div>
                </div>
                <div class="card">
                    <h4>Total Prescriptions</h4>
                    <div id="total-prescriptions" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Prescriptions issued this month</div>
                </div>
                <div class="card">
                    <h4>Staff Count</h4>
                    <div id="staff-count" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Active staff members</div>
                </div>
            </div>

            <!-- Detailed Analytics -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0">
                <!-- Recent Patients -->
                <div class="card">
                    <h4>Recent Patients</h4>
                    <div id="recent-patients">
                        <div style="text-align:center;color:#666;padding:20px">Loading...</div>
                    </div>
                </div>

                <!-- Top Treatments -->
                <div class="card">
                    <h4>Popular Treatments</h4>
                    <div id="popular-treatments">
                        <div style="text-align:center;color:#666;padding:20px">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Schedule Overview -->
            <div class="card">
                <h4>My Schedule Overview</h4>
                <div id="schedule-overview">
                    <div style="text-align:center;color:#666;padding:20px">Loading schedule...</div>
                </div>
            </div>
        </div>

        <!-- Prescriptions Tab -->
        <div id="prescriptions-tab" class="tab-content">
            <div class="card">
                <h4>Prescription Management</h4>
                <div style="margin:15px 0;display:flex;gap:10px">
                    <button class="btn btn-primary" onclick="loadAppointmentsNeedingPrescriptions()">Appointments Needing Prescriptions</button>
                    <button class="btn btn-primary" onclick="loadAllPrescriptions()">View All Prescriptions</button>
                </div>
                
                <div id="appointments-needing-prescriptions" style="margin:20px 0">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <div id="prescriptions-list" style="margin:20px 0">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Staff Management Tab -->
        <div id="staff-tab" class="tab-content">
            <div class="card">
                <h4>Staff Management - <?php echo htmlspecialchars(getBranchName($userBranchId)); ?></h4>
                <div style="margin:15px 0;display:flex;gap:10px">
                    <button class="btn btn-primary" onclick="showAddStaffModal()">Add New Staff</button>
                    <button class="btn btn-primary" onclick="loadBranchStaff()">Reload Staff</button>
                    <button class="btn btn-primary" onclick="showStaffPerformance()">Staff Performance</button>
                </div>
                
                <div id="staff-grid" class="staff-grid" style="margin:20px 0">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <div id="staff-performance" style="margin:20px 0;display:none">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Appointments Tab -->
        <div id="appointments-tab" class="tab-content">
            <div class="card">
                <h4>Daily Appointment Management</h4>
                <div style="margin:15px 0;display:flex;gap:10px;align-items:center">
                    <label>Select Date:</label>
                    <input type="date" id="appointment-date-filter" value="<?php echo date('Y-m-d'); ?>" />
                    <button class="btn btn-primary" onclick="loadDailyAppointments()">Load Appointments</button>
                    <button class="btn btn-primary" onclick="loadMySchedule()">My Schedule</button>
                </div>
                
                <div id="daily-appointments">
                    <div style="text-align:center;color:#666;padding:20px">Select a date and click "Load Appointments"</div>
                </div>
                
                <div id="my-schedule" style="margin-top:20px;display:none">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Credentials Tab -->
        <div id="credentials-tab" class="tab-content">
            <div class="card">
                <h4>Dentist & Clinic Credentials</h4>
                <div style="margin:15px 0">
                    <p>Upload and manage your professional credentials and clinic certifications. These will be displayed in the clinic listing.</p>
                </div>
                
                <!-- Dentist Credentials -->
                <div style="background:#f9f9f9;padding:20px;border-radius:8px;margin:15px 0">
                    <h5>Personal Credentials</h5>
                    <form id="dentist-credentials-form" onsubmit="saveDentistCredentials(event)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:10px 0">
                            <div class="form-group">
                                <label>License Number:</label>
                                <input type="text" id="license-number" required />
                            </div>
                            <div class="form-group">
                                <label>Specialization:</label>
                                <select id="dentist-specialization">
                                    <option value="">Select specialization</option>
                                    <option value="General Dentistry">General Dentistry</option>
                                    <option value="Orthodontics">Orthodontics</option>
                                    <option value="Oral Surgery">Oral Surgery</option>
                                    <option value="Pediatric Dentistry">Pediatric Dentistry</option>
                                    <option value="Periodontics">Periodontics</option>
                                    <option value="Endodontics">Endodontics</option>
                                    <option value="Prosthodontics">Prosthodontics</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Years of Experience:</label>
                                <input type="number" id="experience-years" min="0" />
                            </div>
                            <div class="form-group">
                                <label>Education:</label>
                                <input type="text" id="education" placeholder="e.g., DDS, University Name" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Professional Bio:</label>
                            <textarea id="professional-bio" rows="4" placeholder="Brief description of your expertise and approach..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>License Certificate (PDF/Image):</label>
                            <input type="file" id="license-file" accept=".pdf,.jpg,.jpeg,.png" />
                        </div>
                        <button type="submit" class="btn btn-primary">Save Personal Credentials</button>
                    </form>
                </div>
                
                <!-- Clinic Credentials -->
                <div style="background:#f9f9f9;padding:20px;border-radius:8px;margin:15px 0">
                    <h5>Clinic Credentials</h5>
                    <form id="clinic-credentials-form" onsubmit="saveClinicCredentials(event)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:10px 0">
                            <div class="form-group">
                                <label>Clinic License Number:</label>
                                <input type="text" id="clinic-license" required />
                            </div>
                            <div class="form-group">
                                <label>Business Permit Number:</label>
                                <input type="text" id="business-permit" />
                            </div>
                            <div class="form-group">
                                <label>Accreditations:</label>
                                <input type="text" id="accreditations" placeholder="e.g., DOH, PhilHealth" />
                            </div>
                            <div class="form-group">
                                <label>Established Year:</label>
                                <input type="number" id="established-year" min="1900" max="<?php echo date('Y'); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Services Offered:</label>
                            <textarea id="services-offered" rows="3" placeholder="List of dental services provided..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Clinic Photos:</label>
                            <input type="file" id="clinic-photos" accept=".jpg,.jpeg,.png" multiple />
                            <small style="color:#666">You can select multiple images</small>
                        </div>
                        <div class="form-group">
                            <label>Certifications (PDF/Images):</label>
                            <input type="file" id="clinic-certifications" accept=".pdf,.jpg,.jpeg,.png" multiple />
                        </div>
                        <button type="submit" class="btn btn-primary">Save Clinic Credentials</button>
                    </form>
                </div>
                
                <!-- Current Credentials Display -->
                <div id="current-credentials" style="margin-top:20px">
                    <h5>Current Credentials</h5>
                    <div id="credentials-display">
                        <div style="text-align:center;color:#666;padding:20px">Click "Load Current Credentials" to view</div>
                    </div>
                    <button class="btn btn-primary" onclick="loadCurrentCredentials()">Load Current Credentials</button>
                </div>
            </div>
        </div>

        <div style="text-align:right;color:#666;font-size:0.9rem">Dentist dashboard — Enhanced with prescription management and staff administration</div>
    </div>

    <!-- Prescription Modal -->
    <div id="prescription-modal" class="modal">
        <div class="modal-content">
            <h3>Create/Edit Prescription</h3>
            <form id="prescription-form" onsubmit="savePrescription(event)">
                <input type="hidden" id="prescription-id" />
                <input type="hidden" id="appointment-id" />
                
                <div class="form-group">
                    <label>Patient:</label>
                    <input type="text" id="patient-name" readonly />
                </div>
                
                <div class="form-group">
                    <label>Diagnosis:</label>
                    <textarea id="diagnosis" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Instructions:</label>
                    <textarea id="instructions"></textarea>
                </div>
                
                <h4>Medications:</h4>
                <div id="medications-list"></div>
                <button type="button" onclick="addMedication()" class="btn btn-primary">Add Medication</button>
                
                <div style="margin:20px 0;display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">Save Prescription</button>
                    <button type="button" onclick="closePrescriptionModal()" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Edit Modal -->
    <div id="staff-modal" class="modal">
        <div class="modal-content">
            <h3 id="staff-modal-title">Edit Staff Member</h3>
            <form id="staff-form" onsubmit="saveStaffMember(event)">
                <input type="hidden" id="staff-id" />
                <input type="hidden" id="staff-action" value="update" />
                
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" id="staff-name" required />
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="staff-email" required />
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" id="staff-phone" />
                </div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select id="staff-role">
                        <option value="staff">Staff</option>
                        <option value="dentist">Dentist</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Specialization:</label>
                    <select id="staff-specialization">
                        <option value="">Select specialization</option>
                        <option value="General Dentistry">General Dentistry</option>
                        <option value="Orthodontics">Orthodontics</option>
                        <option value="Oral Surgery">Oral Surgery</option>
                        <option value="Pediatric Dentistry">Pediatric Dentistry</option>
                        <option value="Periodontics">Periodontics</option>
                        <option value="Endodontics">Endodontics</option>
                        <option value="Prosthodontics">Prosthodontics</option>
                        <option value="Dental Hygienist">Dental Hygienist</option>
                        <option value="Dental Assistant">Dental Assistant</option>
                        <option value="Reception">Reception</option>
                    </select>
                </div>
                
                <div id="password-fields" style="display:none">
                    <div class="form-group">
                        <label>Password (leave empty to keep current):</label>
                        <input type="password" id="staff-password" />
                    </div>
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" id="staff-password-confirm" />
                    </div>
                </div>
                
                <div style="margin:20px 0;display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary" id="staff-submit-btn">Save Changes</button>
                    <button type="button" onclick="closeStaffModal()" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div id="appointment-details-modal" class="modal">
        <div class="modal-content">
            <h3>Appointment Details</h3>
            <div id="appointment-details-content">
                <!-- Will be populated by JavaScript -->
            </div>
            <div style="margin:20px 0;display:flex;gap:10px">
                <button type="button" onclick="closeAppointmentDetailsModal()" class="btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        const apiBase = '../api/dentist.php';
        const prescriptionsApi = '../api/prescriptions.php';
        const staffApi = '../api/staff-management.php';

        document.addEventListener('DOMContentLoaded', () => {
            loadComprehensiveAnalytics();
            // Set today's date for appointment filter
            document.getElementById('appointment-date-filter').value = new Date().toISOString().split('T')[0];
        });

        // Tab Management
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active to clicked button
            event.target.classList.add('active');
            
            // Load data for specific tabs
            if (tabName === 'prescriptions') {
                loadAppointmentsNeedingPrescriptions();
            } else if (tabName === 'staff') {
                loadBranchStaff();
            } else if (tabName === 'appointments') {
                loadDailyAppointments();
            } else if (tabName === 'analytics') {
                loadComprehensiveAnalytics();
            } else if (tabName === 'credentials') {
                loadCurrentCredentials();
            }
        }

        // Enhanced Analytics functions
        function loadComprehensiveAnalytics() {
            console.log('Loading comprehensive analytics...');
            fetch(apiBase + '?action=getComprehensiveAnalytics', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response URL:', response.url);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success) {
                        const analytics = data.analytics;
                        
                        // Update summary cards
                        document.getElementById('branch-revenue-month').textContent = '₱ ' + (analytics.branch_revenue_month || 0).toFixed(2);
                        document.getElementById('total-patients').textContent = analytics.total_patients || 0;
                        document.getElementById('appointments-today').textContent = `${analytics.today_pending || 0} / ${analytics.today_approved || 0} / ${analytics.today_completed || 0}`;
                        document.getElementById('pending-count').textContent = analytics.pending_count || 0;
                        document.getElementById('total-prescriptions').textContent = analytics.prescriptions_month || 0;
                        document.getElementById('staff-count').textContent = analytics.staff_count || 0;
                        
                        // Load recent patients
                        loadRecentPatients(analytics.recent_patients || []);
                        
                        // Load popular treatments
                        loadPopularTreatments(analytics.popular_treatments || []);
                        
                        // Load schedule overview
                        loadScheduleOverview(analytics.schedule_overview || []);
                    } else {
                        console.error('API Error:', data.message);
                        alert('Failed to load analytics: ' + data.message);
                    }
                }).catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading analytics data: ' + error.message);
                });
        }

        function loadRecentPatients(patients) {
            const container = document.getElementById('recent-patients');
            if (patients.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No recent patients</div>';
                return;
            }
            
            let html = '<table style="width:100%;font-size:0.9rem"><thead><tr><th>Patient</th><th>Last Visit</th><th>Status</th></tr></thead><tbody>';
            patients.forEach(p => {
                html += `<tr>
                    <td>${p.patient_name}</td>
                    <td>${p.last_visit}</td>
                    <td><span style="color:${p.status === 'completed' ? 'green' : 'orange'}">${p.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function loadPopularTreatments(treatments) {
            const container = document.getElementById('popular-treatments');
            if (treatments.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No treatment data</div>';
                return;
            }
            
            let html = '<table style="width:100%;font-size:0.9rem"><thead><tr><th>Treatment</th><th>Count</th><th>Revenue</th></tr></thead><tbody>';
            treatments.forEach(t => {
                html += `<tr>
                    <td>${t.treatment_name}</td>
                    <td>${t.count}</td>
                    <td>₱${parseFloat(t.revenue || 0).toFixed(2)}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function loadScheduleOverview(schedule) {
            const container = document.getElementById('schedule-overview');
            if (schedule.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No upcoming appointments</div>';
                return;
            }
            
            let html = '<table style="width:100%"><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th></tr></thead><tbody>';
            schedule.forEach(s => {
                html += `<tr>
                    <td>${s.appointment_date}</td>
                    <td>${s.appointment_time}</td>
                    <td>${s.patient_name}</td>
                    <td>${s.treatment_name || 'N/A'}</td>
                    <td><span style="color:${s.status === 'approved' ? 'green' : 'orange'}">${s.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // Enhanced Appointment functions
        function loadDailyAppointments() {
            const date = document.getElementById('appointment-date-filter').value;
            if (!date) {
                alert('Please select a date');
                return;
            }
            
            fetch(apiBase + '?action=getDailyAppointments&date=' + encodeURIComponent(date))
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('daily-appointments');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load appointments') + '</div>';
                        return;
                    }
                    
                    const appointments = data.appointments || [];
                    if (appointments.length === 0) {
                        container.innerHTML = '<div class="card">No appointments found for ' + date + '</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>Appointments for ' + date + '</h4>';
                    html += '<table><thead><tr><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    
                    appointments.forEach(a => {
                        html += `<tr>
                            <td>${a.appointment_time}</td>
                            <td>${a.patient_name}</td>
                            <td>${a.treatment_name || 'N/A'}</td>
                            <td><span style="color:${getStatusColor(a.status)}">${a.status}</span></td>
                            <td class="actions">
                                <button class="btn" onclick="viewAppointmentDetails(${a.id})">Details</button>
                                ${a.status === 'pending' ? `<button class="btn btn-success" onclick="updateStatus(${a.id},'approved')">Approve</button>` : ''}
                                ${a.status === 'approved' ? `<button class="btn btn-primary" onclick="updateStatus(${a.id},'completed')">Complete</button>` : ''}
                                ${a.status === 'completed' && !a.has_prescription ? `<button class="btn btn-primary" onclick="createPrescription(${a.id}, '${a.patient_name}')">Add Prescription</button>` : ''}
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('daily-appointments').innerHTML = '<div class="card">Network error loading appointments</div>';
                });
        }

        function loadMySchedule() {
            fetch(apiBase + '?action=getMySchedule')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('my-schedule');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load schedule') + '</div>';
                        return;
                    }
                    
                    const schedule = data.schedule || [];
                    if (schedule.length === 0) {
                        container.innerHTML = '<div class="card">No upcoming appointments in your schedule</div>';
                        container.style.display = 'block';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>My Upcoming Schedule</h4>';
                    html += '<table><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th></tr></thead><tbody>';
                    
                    schedule.forEach(s => {
                        html += `<tr>
                            <td>${s.appointment_date}</td>
                            <td>${s.appointment_time}</td>
                            <td>${s.patient_name}</td>
                            <td>${s.treatment_name || 'N/A'}</td>
                            <td><span style="color:${getStatusColor(s.status)}">${s.status}</span></td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                    container.style.display = 'block';
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('my-schedule').innerHTML = '<div class="card">Network error loading schedule</div>';
                });
        }

        function getStatusColor(status) {
            switch(status) {
                case 'pending': return 'orange';
                case 'approved': return 'blue';
                case 'completed': return 'green';
                case 'cancelled': return 'red';
                default: return 'gray';
            }
        }

        function viewAppointmentDetails(appointmentId) {
            fetch(apiBase + '?action=getAppointmentDetails&appointment_id=' + appointmentId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const appointment = data.appointment;
                        const content = document.getElementById('appointment-details-content');
                        content.innerHTML = `
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                                <div><strong>Patient:</strong> ${appointment.patient_name}</div>
                                <div><strong>Date:</strong> ${appointment.appointment_date}</div>
                                <div><strong>Time:</strong> ${appointment.appointment_time}</div>
                                <div><strong>Status:</strong> ${appointment.status}</div>
                                <div><strong>Treatment:</strong> ${appointment.treatment_name || 'N/A'}</div>
                                <div><strong>Phone:</strong> ${appointment.patient_phone || 'N/A'}</div>
                            </div>
                            ${appointment.notes ? `<div style="margin-top:15px"><strong>Notes:</strong><br>${appointment.notes}</div>` : ''}
                            ${appointment.prescription ? `<div style="margin-top:15px"><strong>Prescription:</strong> Yes</div>` : ''}
                        `;
                        document.getElementById('appointment-details-modal').classList.add('show');
                    } else {
                        alert('Error loading appointment details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert('Network error loading appointment details');
                });
        }

        function closeAppointmentDetailsModal() {
            document.getElementById('appointment-details-modal').classList.remove('show');
        }

        function updateStatus(id, status) {
            if (!confirm('Confirm change to ' + status + '?')) return;
            fetch(apiBase + '?action=updateStatus', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({appointment_id: id, status: status})
            }).then(r=>r.json()).then(data=>{
                if (data.success) { 
                    alert(data.message || 'Updated'); 
                    loadDailyAppointments();
                    loadComprehensiveAnalytics(); 
                    
                    // If completed, check if prescription needs to be sent
                    if (status === 'completed') {
                        setTimeout(() => {
                            if (confirm('Send invoice and prescription to patient email?')) {
                                sendInvoiceWithPrescription(id);
                            }
                        }, 1000);
                    }
                }
                else alert('Error: ' + (data.message||'Update failed'));
            }).catch(e=>{console.error(e); alert('Network error')});
        }

        function sendInvoiceWithPrescription(appointmentId) {
            fetch(apiBase + '?action=sendInvoiceWithPrescription', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({appointment_id: appointmentId})
            }).then(r=>r.json()).then(data=>{
                if (data.success) {
                    alert('Invoice and prescription sent successfully!');
                } else {
                    alert('Error sending email: ' + (data.message || 'Unknown error'));
                }
            }).catch(e=>{
                console.error(e);
                alert('Network error sending email');
            });
        }

        // Prescription Management Functions
        function loadAppointmentsNeedingPrescriptions() {
            fetch(prescriptionsApi + '?action=getAppointmentsNeedingPrescriptions')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('appointments-needing-prescriptions');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load') + '</div>';
                        return;
                    }
                    
                    const appointments = data.appointments || [];
                    if (appointments.length === 0) {
                        container.innerHTML = '<div class="card">No appointments need prescriptions currently.</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>Appointments Needing Prescriptions</h4><table><thead><tr><th>Patient</th><th>Date</th><th>Treatment</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                    appointments.forEach(a => {
                        html += `<tr>
                            <td>${a.patient_name}</td>
                            <td>${a.appointment_date}</td>
                            <td>${a.treatment_name || 'N/A'}</td>
                            <td>${a.status}</td>
                            <td><button class="btn btn-primary" onclick="createPrescription(${a.id}, '${a.patient_name}')">Create Prescription</button></td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('appointments-needing-prescriptions').innerHTML = '<div class="card">Network error loading appointments</div>';
                });
        }

        function loadAllPrescriptions() {
            fetch(prescriptionsApi + '?action=getPrescriptions')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('prescriptions-list');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load') + '</div>';
                        return;
                    }
                    
                    const prescriptions = data.prescriptions || [];
                    if (prescriptions.length === 0) {
                        container.innerHTML = '<div class="card">No prescriptions found.</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>All Prescriptions</h4><table><thead><tr><th>Patient</th><th>Date</th><th>Diagnosis</th><th>Medications</th><th>Actions</th></tr></thead><tbody>';
                    prescriptions.forEach(p => {
                        html += `<tr>
                            <td>${p.patient_name}</td>
                            <td>${p.created_at}</td>
                            <td>${p.diagnosis}</td>
                            <td>${p.medication_count} medications</td>
                            <td>
                                <button class="btn btn-primary" onclick="editPrescription(${p.id})">Edit</button>
                                <button class="btn" onclick="viewPrescription(${p.id})">View</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('prescriptions-list').innerHTML = '<div class="card">Network error loading prescriptions</div>';
                });
        }

        // Prescription Modal Functions
        function createPrescription(appointmentId, patientName) {
            document.getElementById('prescription-id').value = '';
            document.getElementById('appointment-id').value = appointmentId;
            document.getElementById('patient-name').value = patientName;
            document.getElementById('diagnosis').value = '';
            document.getElementById('instructions').value = '';
            document.getElementById('medications-list').innerHTML = '';
            addMedication(); // Start with one medication
            document.getElementById('prescription-modal').classList.add('show');
        }

        function editPrescription(prescriptionId) {
            // TODO: Load existing prescription data
            alert('Edit prescription feature coming soon');
        }

        function viewPrescription(prescriptionId) {
            // TODO: Show prescription details
            alert('View prescription feature coming soon');
        }

        function addMedication() {
            const container = document.getElementById('medications-list');
            const medId = 'med_' + Date.now();
            const html = `
                <div class="medication-item" id="${medId}">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <h5>Medication</h5>
                        <button type="button" onclick="removeMedication('${medId}')" class="btn btn-danger">Remove</button>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group">
                            <label>Medicine Name:</label>
                            <input type="text" name="medication_name[]" required />
                        </div>
                        <div class="form-group">
                            <label>Dosage:</label>
                            <input type="text" name="dosage[]" required />
                        </div>
                        <div class="form-group">
                            <label>Frequency:</label>
                            <select name="frequency[]" required>
                                <option value="">Select frequency</option>
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily">Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="Four times daily">Four times daily</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration:</label>
                            <input type="text" name="duration[]" placeholder="e.g., 7 days" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Instructions:</label>
                        <textarea name="medication_instructions[]" placeholder="e.g., Take with food"></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function removeMedication(medId) {
            document.getElementById(medId).remove();
        }

        function savePrescription(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                appointment_id: document.getElementById('appointment-id').value,
                diagnosis: formData.get('diagnosis') || document.getElementById('diagnosis').value,
                instructions: formData.get('instructions') || document.getElementById('instructions').value,
                medications: []
            };
            
            // Collect medications data
            const names = formData.getAll('medication_name[]');
            const dosages = formData.getAll('dosage[]');
            const frequencies = formData.getAll('frequency[]');
            const durations = formData.getAll('duration[]');
            const instructions = formData.getAll('medication_instructions[]');
            
            for (let i = 0; i < names.length; i++) {
                if (names[i]) {
                    data.medications.push({
                        medication_name: names[i],
                        dosage: dosages[i],
                        frequency: frequencies[i],
                        duration: durations[i],
                        instructions: instructions[i] || '',
                        form: 'tablet',
                        quantity: '1',
                        with_food: false,
                        is_priority: false
                    });
                }
            }
            
            fetch(prescriptionsApi + '?action=createPrescription', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Prescription saved successfully!');
                    closePrescriptionModal();
                    loadAppointmentsNeedingPrescriptions();
                    loadAllPrescriptions();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save prescription'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving prescription');
            });
        }

        function closePrescriptionModal() {
            document.getElementById('prescription-modal').classList.remove('show');
        }

        // Enhanced Staff Management Functions
        function showAddStaffModal() {
            document.getElementById('staff-modal-title').textContent = 'Add New Staff Member';
            document.getElementById('staff-action').value = 'create';
            document.getElementById('staff-submit-btn').textContent = 'Create Staff';
            
            // Clear form
            document.getElementById('staff-id').value = '';
            document.getElementById('staff-name').value = '';
            document.getElementById('staff-email').value = '';
            document.getElementById('staff-phone').value = '';
            document.getElementById('staff-role').value = 'staff';
            document.getElementById('staff-specialization').value = '';
            document.getElementById('staff-password').value = '';
            document.getElementById('staff-password-confirm').value = '';
            
            // Show password fields for new staff
            document.getElementById('password-fields').style.display = 'block';
            document.getElementById('staff-password').required = true;
            document.getElementById('staff-password-confirm').required = true;
            
            document.getElementById('staff-modal').classList.add('show');
        }

        function loadBranchStaff() {
            fetch(staffApi + '?action=getBranchStaff')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('staff-grid');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load staff') + '</div>';
                        return;
                    }
                    
                    const staff = data.staff || [];
                    if (staff.length === 0) {
                        container.innerHTML = '<div class="card">No staff found in your branch.</div>';
                        return;
                    }
                    
                    let html = '';
                    staff.forEach(s => {
                        html += `
                            <div class="staff-card">
                                <div class="staff-header">
                                    <h4>${s.name}</h4>
                                    <div style="display:flex;gap:10px">
                                        <button class="btn btn-primary" onclick="editStaff(${s.id}, '${s.name}', '${s.email}', '${s.phone || ''}', '${s.role || 'staff'}', '${s.specialization || ''}')">Edit</button>
                                        <button class="btn btn-danger" onclick="deleteStaff(${s.id}, '${s.name}')">Delete</button>
                                    </div>
                                </div>
                                <div><strong>Role:</strong> ${s.role}</div>
                                <div><strong>Email:</strong> ${s.email}</div>
                                ${s.phone ? '<div><strong>Phone:</strong> ' + s.phone + '</div>' : ''}
                                ${s.specialization ? '<div><strong>Specialization:</strong> ' + s.specialization + '</div>' : ''}
                                <div><strong>Status:</strong> ${s.is_active ? 'Active' : 'Inactive'}</div>
                                ${s.last_login ? '<div><strong>Last Login:</strong> ' + s.last_login + '</div>' : ''}
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('staff-grid').innerHTML = '<div class="card">Network error loading staff</div>';
                });
        }

        function editStaff(id, name, email, phone, role, specialization) {
            document.getElementById('staff-modal-title').textContent = 'Edit Staff Member';
            document.getElementById('staff-action').value = 'update';
            document.getElementById('staff-submit-btn').textContent = 'Save Changes';
            
            document.getElementById('staff-id').value = id;
            document.getElementById('staff-name').value = name;
            document.getElementById('staff-email').value = email;
            document.getElementById('staff-phone').value = phone;
            document.getElementById('staff-role').value = role;
            document.getElementById('staff-specialization').value = specialization;
            
            // Hide password fields for existing staff
            document.getElementById('password-fields').style.display = 'none';
            document.getElementById('staff-password').required = false;
            document.getElementById('staff-password-confirm').required = false;
            
            document.getElementById('staff-modal').classList.add('show');
        }

        function deleteStaff(id, name) {
            if (!confirm(`Are you sure you want to delete staff member "${name}"? This action cannot be undone.`)) {
                return;
            }
            
            fetch(staffApi + '?action=deleteStaffMember', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({staff_id: id})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Staff member deleted successfully!');
                    loadBranchStaff();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete staff member'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error deleting staff member');
            });
        }

        function saveStaffMember(event) {
            event.preventDefault();
            
            const action = document.getElementById('staff-action').value;
            const password = document.getElementById('staff-password').value;
            const passwordConfirm = document.getElementById('staff-password-confirm').value;
            
            // Validate passwords if creating new staff or if password is provided
            if (action === 'create' || password) {
                if (password !== passwordConfirm) {
                    alert('Passwords do not match!');
                    return;
                }
                if (password.length < 6) {
                    alert('Password must be at least 6 characters long!');
                    return;
                }
            }
            
            const data = {
                staff_id: document.getElementById('staff-id').value,
                name: document.getElementById('staff-name').value,
                email: document.getElementById('staff-email').value,
                phone: document.getElementById('staff-phone').value,
                role: document.getElementById('staff-role').value,
                specialization: document.getElementById('staff-specialization').value
            };
            
            if (password) {
                data.password = password;
            }
            
            const apiAction = action === 'create' ? 'createStaffMember' : 'updateStaffMember';
            
            fetch(staffApi + '?action=' + apiAction, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(action === 'create' ? 'Staff member created successfully!' : 'Staff member updated successfully!');
                    closeStaffModal();
                    loadBranchStaff();
                    loadComprehensiveAnalytics(); // Refresh staff count
                } else {
                    alert('Error: ' + (data.message || 'Failed to save staff member'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving staff member');
            });
        }

        function showStaffPerformance() {
            fetch(staffApi + '?action=getStaffPerformance')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('staff-performance');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load performance data') + '</div>';
                        return;
                    }
                    
                    const performance = data.performance || [];
                    if (performance.length === 0) {
                        container.innerHTML = '<div class="card">No performance data available.</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>Staff Performance</h4><table><thead><tr><th>Staff</th><th>Appointments</th><th>Revenue</th><th>Avg Rating</th></tr></thead><tbody>';
                    performance.forEach(p => {
                        html += `<tr>
                            <td>${p.staff_name}</td>
                            <td>${p.appointment_count || 0}</td>
                            <td>₱${(p.total_revenue || 0).toFixed(2)}</td>
                            <td>${p.avg_rating ? parseFloat(p.avg_rating).toFixed(1) + '/5' : 'N/A'}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                    container.style.display = 'block';
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('staff-performance').innerHTML = '<div class="card">Network error loading performance data</div>';
                });
        }

        // Credentials Management Functions
        function loadCurrentCredentials() {
            fetch(apiBase + '?action=getCurrentCredentials')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('credentials-display');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">Error loading credentials: ' + (data.message || 'Unknown error') + '</div>';
                        return;
                    }
                    
                    const credentials = data.credentials || {};
                    let html = '';
                    
                    if (credentials.dentist) {
                        const d = credentials.dentist;
                        html += `
                            <div style="background:#f9f9f9;padding:15px;border-radius:8px;margin:10px 0">
                                <h5>Personal Credentials</h5>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <div><strong>License:</strong> ${d.license_number || 'Not set'}</div>
                                    <div><strong>Specialization:</strong> ${d.specialization || 'Not set'}</div>
                                    <div><strong>Experience:</strong> ${d.experience_years || 'Not set'} years</div>
                                    <div><strong>Education:</strong> ${d.education || 'Not set'}</div>
                                </div>
                                ${d.professional_bio ? `<div style="margin-top:10px"><strong>Bio:</strong><br>${d.professional_bio}</div>` : ''}
                                ${d.license_file ? `<div style="margin-top:10px"><strong>License File:</strong> <a href="${d.license_file}" target="_blank">View Document</a></div>` : ''}
                            </div>
                        `;
                        
                        // Pre-fill form
                        document.getElementById('license-number').value = d.license_number || '';
                        document.getElementById('dentist-specialization').value = d.specialization || '';
                        document.getElementById('experience-years').value = d.experience_years || '';
                        document.getElementById('education').value = d.education || '';
                        document.getElementById('professional-bio').value = d.professional_bio || '';
                    }
                    
                    if (credentials.clinic) {
                        const c = credentials.clinic;
                        html += `
                            <div style="background:#f9f9f9;padding:15px;border-radius:8px;margin:10px 0">
                                <h5>Clinic Credentials</h5>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <div><strong>Clinic License:</strong> ${c.clinic_license || 'Not set'}</div>
                                    <div><strong>Business Permit:</strong> ${c.business_permit || 'Not set'}</div>
                                    <div><strong>Accreditations:</strong> ${c.accreditations || 'Not set'}</div>
                                    <div><strong>Established:</strong> ${c.established_year || 'Not set'}</div>
                                </div>
                                ${c.services_offered ? `<div style="margin-top:10px"><strong>Services:</strong><br>${c.services_offered}</div>` : ''}
                            </div>
                        `;
                        
                        // Pre-fill form
                        document.getElementById('clinic-license').value = c.clinic_license || '';
                        document.getElementById('business-permit').value = c.business_permit || '';
                        document.getElementById('accreditations').value = c.accreditations || '';
                        document.getElementById('established-year').value = c.established_year || '';
                        document.getElementById('services-offered').value = c.services_offered || '';
                    }
                    
                    if (html === '') {
                        html = '<div style="text-align:center;color:#666;padding:20px">No credentials uploaded yet</div>';
                    }
                    
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('credentials-display').innerHTML = '<div style="text-align:center;color:#666;padding:20px">Network error loading credentials</div>';
                });
        }

        function saveDentistCredentials(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('license_number', document.getElementById('license-number').value);
            formData.append('specialization', document.getElementById('dentist-specialization').value);
            formData.append('experience_years', document.getElementById('experience-years').value);
            formData.append('education', document.getElementById('education').value);
            formData.append('professional_bio', document.getElementById('professional-bio').value);
            
            const licenseFile = document.getElementById('license-file').files[0];
            if (licenseFile) {
                formData.append('license_file', licenseFile);
            }
            
            fetch(apiBase + '?action=saveDentistCredentials', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Personal credentials saved successfully!');
                    loadCurrentCredentials();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save credentials'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving credentials');
            });
        }

        function saveClinicCredentials(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('clinic_license', document.getElementById('clinic-license').value);
            formData.append('business_permit', document.getElementById('business-permit').value);
            formData.append('accreditations', document.getElementById('accreditations').value);
            formData.append('established_year', document.getElementById('established-year').value);
            formData.append('services_offered', document.getElementById('services-offered').value);
            
            const clinicPhotos = document.getElementById('clinic-photos').files;
            for (let i = 0; i < clinicPhotos.length; i++) {
                formData.append('clinic_photos[]', clinicPhotos[i]);
            }
            
            const certifications = document.getElementById('clinic-certifications').files;
            for (let i = 0; i < certifications.length; i++) {
                formData.append('clinic_certifications[]', certifications[i]);
            }
            
            fetch(apiBase + '?action=saveClinicCredentials', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Clinic credentials saved successfully!');
                    loadCurrentCredentials();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save credentials'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving credentials');
            });
        }

        function closeStaffModal() {
            document.getElementById('staff-modal').classList.remove('show');
        }

        function logout(){
            fetch('../../src/controllers/AuthController.php?action=logout',{method:'POST'})
            .then(r=>r.json()).then(()=>window.location.href='../auth/login.php').catch(()=>window.location.href='../auth/login.php');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const prescriptionModal = document.getElementById('prescription-modal');
            const staffModal = document.getElementById('staff-modal');
            const appointmentModal = document.getElementById('appointment-details-modal');
            
            if (event.target === prescriptionModal) {
                closePrescriptionModal();
            }
            if (event.target === staffModal) {
                closeStaffModal();
            }
            if (event.target === appointmentModal) {
                closeAppointmentDetailsModal();
            }
        }
    </script>
</body>
</html>
