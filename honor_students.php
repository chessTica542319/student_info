<?php
// Protect this page - require authentication
include 'auth.php';

include 'db.php';

// Search value from user
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_active = $search !== '';
$search_valid = $search_active && preg_match('/^[a-zA-Z0-9. ]+$/', $search);

// Get honor students (gwa >= 90.0)
if ($search_valid) {
    $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
    $conditions = [];
    $types = '';
    $params = [];

    foreach ($tokens as $token) {
        if (preg_match('/^[0-9.]+$/', $token)) {
            $tokenValue = floatval($token);
            if ($tokenValue < 90.0 || $tokenValue > 100.0) {
                // numeric search outside GWA range cannot match an honor student
                $conditions = ['0=1'];
                break;
            }
            $conditions[] = 'gwa = ?';
            $types .= 'd';
            $params[] = $tokenValue;
        } else {
            $conditions[] = '(LOWER(f_name) LIKE ? OR LOWER(m_name) LIKE ? OR LOWER(l_name) LIKE ? OR LOWER(CONCAT_WS(" ", f_name, m_name, l_name)) LIKE ? OR LOWER(course) LIKE ?)';
            $types .= 'sssss';
            $like = '%' . strtolower($token) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
    }

    if (count($conditions) > 0) {
        $sql = 'SELECT id, f_name, m_name, l_name, gender, gwa, course FROM student WHERE gwa >= 90.0 AND ' . implode(' AND ', $conditions) . ' ORDER BY gwa DESC';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($types !== '') {
                $bindParams = array_merge([$types], $params);
                $tmp = [];
                foreach ($bindParams as $key => $value) {
                    $tmp[$key] = &$bindParams[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $tmp);
            }
            $stmt->execute();
            $honor_result = $stmt->get_result();
        } else {
            $honor_result = false;
        }
    } else {
        $honor_result = false;
    }
} elseif ($search_active) {
    // Search is active but invalid: no results
    $honor_result = false;
} else {
$honor_sql = 'SELECT id, f_name, m_name, l_name, gender, gwa, course FROM student WHERE gwa >= 90 ORDER BY gwa DESC';
    $honor_result = $conn->query($honor_sql);
}


try {
    $total_honor = $honor_result ? $honor_result->num_rows : 0;
} catch (Exception $e) {
    $total_honor = 0;
}

$search_message = 'Add GWA ≥90 students first';
if ($search_active && $total_honor === 0) {
    $search_message = 'No matching honor students found. Use Clear to restore the full list.';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Honor Students - SaaS Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* INLINE - ALL READY */
        @font-face{font-family:'Playfair';src:local('serif');font-weight:400;font-style:normal;}
        @font-face{font-family:'Playfair';src:local('serif');font-weight:700;font-style:normal;}
        @font-face{font-family:'Playfair';src:local('serif');font-weight:900;font-style:normal;}
        
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0fdf4;font-family:'Segoe UI',Tahoma,sans-serif;min-height:100vh;}
        .sidebar{width:260px;background:linear-gradient(180deg,#22c55e 0%,#15803d 100%);min-height:100vh;position:fixed;padding:30px 20px;left:0;top:0;}
        .logo{color:#ffffff;font-size:24px;font-weight:700;margin-bottom:40px;text-align:center;}
        .nav-link{display:block;color:#d1fae5;padding:14px 20px;text-decoration:none;border-radius:10px;margin-bottom:8px;font-weight:600;transition:all 0.3s;text-shadow:0 1px 2px rgba(0,0,0,0.3);}
        .nav-link:hover,.nav-link.active{background:rgba(255,255,255,0.1);color:#ffffff;}
        .main-content{margin-left:260px;padding:30px;}
        .header{background:#ffffff;padding:20px 30px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:30px;}
        .header h2{color:#1e293b;font-size:24px;font-weight:600;}
        .stat-card{background:#22c55e;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:30px;}
        .stat-card h3{color:rgba(255,255,255,0.8);font-size:14px;font-weight:600;margin-bottom:8px;}
        .stat-card .value{color:#ffffff;font-size:32px;font-weight:700;}
        .data-card{background:#ffffff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);overflow:hidden;}
        .data-card-header{padding:20px 24px;border-bottom:1px solid #e2e8f0;}
        .data-card-header h3{color:#1e293b;font-size:18px;font-weight:600;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:16px 24px;text-align:left;border-bottom:1px solid #e2e8f0;}
        th{background:#f8fafc;color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;}
        tr:hover{background:#f8fafc;}
        .grade-badge{background:#22c55e;color:#ffffff;padding:6px 12px;border-radius:20px;font-weight:700;}
        .text-center{text-align:center;color:#64748b;font-style:italic;}
        .footer{text-align:center;padding:20px;color:#94a3b8;font-size:13px;margin-top:30px;}

        .btn-cert{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;transition:all 0.3s;box-shadow:0 2px 8px rgba(245,158,11,0.3);}
        .btn-cert:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,158,11,0.5);}
        .cert-modal{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.85);pointer-events:none;}
        .cert-modal.active{pointer-events:auto;}
        .modal-content{background:#ffffff;margin:8% auto;padding:25px;border-radius:16px;width:90%;max-width:850px;max-height:80vh;overflow:auto;position:relative;box-shadow:0 25px 70px rgba(0,0,0,0.4);pointer-events:auto;}
        .close{color:#94a3b8;float:right;font-size:34px;font-weight:bold;cursor:pointer;line-height:1;transition:color 0.3s;}
        .close:hover{color:#1e293b;}
        .cert-preview{width:100%;max-width:800px;margin:0 auto;border:3px solid #f3f4f6;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.2);}
        .btn-group{display:flex;justify-content:center;flex-wrap:wrap;gap:15px;margin-top:20px;}
        .btn-download{background:linear-gradient(135deg,#059669,#047857);color:white;border:none;padding:14px 28px;border-radius:10px;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.3s;box-shadow:0 4px 15px rgba(5,150,105,0.4);}
        .btn-download:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(5,150,105,0.6);}
        .btn-close{background:#6b7280;color:white;border:none;padding:14px 28px;border-radius:10px;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.3s;box-shadow:0 4px 15px rgba(107,114,128,0.3);}
        .btn-close:hover{transform:translateY(-2px);background:#4b5563;box-shadow:0 8px 25px rgba(107,114,128,0.5);}

        /* EXACT POSITIONING */
.cert-container{width:800px;height:566px;margin:0 auto;background:#ffffff;position:relative;font-family:'Playfair',serif;-webkit-print-color-adjust:exact;color-adjust:exact;}
        .cert-border{position:absolute;inset:-10px;background:conic-gradient(#facc15,#22c55e,#facc15);border-radius:20px;z-index:0;}
        .cert-content{position:absolute;width:100%;height:100%;padding:0 40px;top:0;left:0;box-sizing:border-box;z-index:1;}
        
        .cert-title{position:absolute;top:17.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:34px;font-weight:900;color:#1e293b;letter-spacing:2px;text-transform:uppercase;line-height:1;width:100%;}
        .cert-certify{position:absolute;top:30%;left:50%;transform:translateX(-50%);text-align:center;font-size:17px;color:#374151;font-weight:500;line-height:1.4;width:100%;}
        .student-name{position:absolute;top:45%;left:50%;transform:translateX(-50%);text-align:center;font-size:36px;font-weight:900;color:#1e293b;letter-spacing:1px;line-height:1.1;width:100%;}
        .cert-main-text{position:absolute;top:57.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;}
        .honor-badge{position:absolute;top:70%;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;padding:13px 36px;border-radius:30px;font-size:21px;font-weight:900;box-shadow:0 6px 20px rgba(220,38,38,0.4);letter-spacing:1px;text-transform:uppercase;}
        .cert-issue{position:absolute;top:87.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;}

        @media (max-width:768px){
            .cert-container{width:95vw;height:68vw;}
            .cert-title{top:18%;font-size:28px;}
            .cert-certify{top:31%;font-size:15px;}
            .student-name{top:45.5%;font-size:30px;}
            .cert-main-text{top:58%;font-size:14px;}
            .honor-badge{top:70.5%;font-size:19px;padding:11px 30px;}
            .cert-issue{top:87%;font-size:14px;}
        }

        .loading{text-align:center;padding:45px;color:#64748b;font-size:16px;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Student<br>Management</div>
        <a href="index.php" class="nav-link">Dashboard</a>
        <a href="add_student.php" class="nav-link">Add Student</a>
        <a href="honor_students.php" class="nav-link active">Honor Students</a>
        <a href="fail_students.php" class="nav-link">Failed Students</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure?');">Logout</a>
    </div>

    <div class="main-content">
        <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
            <h2 style="margin-right:8px;white-space:nowrap;">Top Honor Students</h2>
            <form action="" method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end;min-width:320px;">

                <div style="position:relative;width:360px;max-width:90vw;">
<input type="text" id="searchInput" name="search" placeholder="Search GWA, Course, Name" value="<?php echo htmlspecialchars($search); ?>" style="padding: 12px 40px 12px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; width: 100%; box-sizing: border-box;" />
                    <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
                </div>
                <button type="submit" id="searchBtn" style="padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Search</button>
                <?php if ($search_active): ?>
                    <a href="honor_students.php" style="padding: 12px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="stat-card">
<h3>Total Honor Students (GWA ≥ 90)</h3>
            <div class="value"><?php echo $total_honor; ?></div>
        </div>
        <div class="data-card">
            <div class="data-card-header"><h3>Honor Roll List</h3></div>
            <table>

                <thead><tr><th>ID</th><th>First</th><th>Middle</th><th>Last</th><th>Gender</th><th>GWA</th><th>Course</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if($honor_result&&$honor_result->num_rows>0){
                        while($row=$honor_result->fetch_assoc()){
                            $name=trim($row['f_name'].' '.$row['m_name'].' '.$row['l_name']);
echo "<tr><td>".$row['id']."</td><td>".$row['f_name']."</td><td>".$row['m_name']."</td><td>".$row['l_name']."</td><td>".$row['gender']."</td><td><span class='grade-badge'>".$row['gwa']."</span></td><td>".$row['course']."</td><td><button class='btn-cert' onclick='showCert({id:".$row['id'].",name:\"".addslashes($name)."\",gwa:".$row['gwa'].",course:\"".addslashes($row['course'])."\"})'> Certificate</button></td></tr>";
                        }
                    }else echo "<tr><td colspan=8 class='text-center'>Add GWA ≥90.0 students first</td></tr>"; ?>
                </tbody>
            </table>
        </div>
        <div class="footer">© <?php echo date('Y');?> @ RudaDev. All Right Reserved.</div>
    </div>

    <div id="certModal" class="cert-modal">
        <div class="modal-content">
            <span class="close" onclick="hideCert()">&times;</span>
            <h3 style="text-align:center;color:#1e293b;margin-bottom:20px;font-size:22px;">Certificate Preview</h3>
            <div class="cert-preview" id="certPreview"><div class="loading">📄 Clean Export Ready</div></div>
            <div class="btn-group">
                <button class="btn-download" onclick="downloadPNG()">Download PNG</button>
                <button class="btn-close" onclick="hideCert()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let certData = {};

        function showCert(data){
            certData = data; // Fresh data assignment
const preview = document.getElementById('certPreview');
            const honor = data.gwa >= 96 ? 'WITH HIGH HONOR' : 'WITH HONOR';
            
            // Fresh DOM every time with inline styles
            preview.innerHTML = `
                <div class="cert-container" style="width:800px;height:566px;margin:0 auto;background:#ffffff;position:relative;font-family:'Playfair',serif;-webkit-print-color-adjust:exact;color-adjust:exact;">
                    <div style="position:absolute;inset:-10px;background:conic-gradient(#facc15,#22c55e,#facc15);border-radius:20px;z-index:0;"></div>
                    <div style="position:absolute;width:100%;height:100%;padding:0 40px;top:0;left:0;box-sizing:border-box;z-index:1;">
                        <div style="position:absolute;top:17.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:34px;font-weight:900;color:#1e293b;letter-spacing:2px;text-transform:uppercase;line-height:1;width:100%;">CERTIFICATE OF EXCELLENCE</div>
                        <div style="position:absolute;top:30%;left:50%;transform:translateX(-50%);text-align:center;font-size:17px;color:#374151;font-weight:500;line-height:1.4;width:100%;">This is to certify that</div>
                        <div style="position:absolute;top:45%;left:50%;transform:translateX(-50%);text-align:center;font-size:36px;font-weight:900;color:#1e293b;letter-spacing:1px;line-height:1.1;width:100%;">${data.name}</div>
                        <div style="position:absolute;top:57.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;">
has successfully completed the requirements for <strong>${data.course}</strong> at <strong>${data.gwa}%</strong> classified as
                        </div>
                        <div style="position:absolute;top:70%;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;padding:13px 36px;border-radius:30px;font-size:21px;font-weight:900;box-shadow:0 6px 20px rgba(220,38,38,0.4);letter-spacing:1px;text-transform:uppercase;">${honor}</div>
                        <div style="position:absolute;top:87.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;">
                            Issued on this day, <strong>${new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}</strong>, to acknowledge exceptional commitment to learning and personal growth.
                        </div>
                    </div>
                </div>`;
            
            const modal = document.getElementById('certModal');
            modal.classList.add('active');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.body.style.pointerEvents = 'none';
            modal.style.pointerEvents = 'auto';
        }

        function hideCert(){
            const modal = document.getElementById('certModal');
            modal.classList.remove('active');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.body.style.pointerEvents = 'auto';
            document.body.offsetHeight;
        }

        async function downloadPNG(){
            const preview = document.getElementById('certPreview');
            const cont = preview.querySelector('.cert-container');
            
            if(!cont){
                preview.innerHTML = '<div class="loading">Export unavailable. Open certificate first.</div>';
                return;
            }

            const safeName = String(certData.name || 'student').replace(/[^a-zA-Z0-9]+/g, '_');
            const fileName = `certificate_${safeName}_${Date.now()}.png`;

            // Load html2canvas if not available
            if(!window.html2canvas){
                try{
                    await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                }catch(err){
                    preview.innerHTML = '<div class="loading">Export failed - html2canvas load error</div>';
                    console.error('Script load error:', err);
                    return;
                }
            }

            if(!window.html2canvas){
                preview.innerHTML = '<div class="loading">Export failed - html2canvas not available</div>';
                return;
            }

            preview.innerHTML = '<div class="loading">⚡ Exporting Certificate...</div>';

            try{
                // Create a temporary render container
                const tempContainer = document.createElement('div');
                tempContainer.style.position = 'fixed';
                tempContainer.style.left = '-9999px';
                tempContainer.style.top = '-9999px';
                tempContainer.style.width = '800px';
                tempContainer.style.height = '566px';
                
const honor = certData.gwa >= 96 ? 'WITH HIGH HONOR' : 'WITH HONOR';
                tempContainer.innerHTML = `
                    <div style="width:100%;height:100%;background:#ffffff;position:relative;font-family:serif;border:10px solid #facc15;box-sizing:border-box;overflow:hidden;">
                        <div style="position:absolute;top:0;left:0;right:0;bottom:0;border:3px solid #22c55e;margin:8px;box-sizing:border-box;"></div>
                        <div style="position:relative;width:100%;height:100%;padding:40px;box-sizing:border-box;z-index:1;display:flex;flex-direction:column;justify-content:space-around;align-items:center;text-align:center;">
                            <div style="font-size:32px;font-weight:bold;color:#1e293b;letter-spacing:2px;text-transform:uppercase;margin-top:20px;">CERTIFICATE OF EXCELLENCE</div>
                            <div style="font-size:16px;color:#374151;margin:10px 0;">This is to certify that</div>
                            <div style="font-size:36px;font-weight:bold;color:#1e293b;margin:20px 0;letter-spacing:1px;">${certData.name}</div>
<div style="font-size:14px;color:#374151;line-height:1.6;max-width:600px;">
                                has successfully completed the requirements for <strong>${certData.course}</strong> at <strong>${certData.gwa}%</strong> classified as
                            </div>
                            <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;padding:12px 30px;border-radius:25px;font-size:18px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;margin:15px 0;border:2px solid #991b1b;">${honor}</div>
                            <div style="font-size:13px;color:#374151;line-height:1.6;max-width:600px;">
                                Issued on this day, <strong>${new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}</strong>, to acknowledge exceptional commitment to learning and personal growth.
                            </div>
                        </div>
                    </div>`;
                
                document.body.appendChild(tempContainer);
                
                const canvas = await window.html2canvas(tempContainer, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 5000
                });

                document.body.removeChild(tempContainer);

                if(!canvas){
                    preview.innerHTML = '<div class="loading">Export failed - canvas generation error</div>';
                    return;
                }

                canvas.toBlob(function(blob){
                    if(!blob){
                        preview.innerHTML = '<div class="loading">Export failed - PNG generation error</div>';
                        return;
                    }

                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    setTimeout(() => URL.revokeObjectURL(url), 500);

                    // Restore preview
const honorText = certData.gwa >= 96 ? 'WITH HIGH HONOR' : 'WITH HONOR';
                    preview.innerHTML = `
                        <div class="cert-container" style="width:800px;height:566px;margin:0 auto;background:#ffffff;position:relative;font-family:'Playfair',serif;-webkit-print-color-adjust:exact;color-adjust:exact;">
                            <div style="position:absolute;inset:-10px;background:conic-gradient(#facc15,#22c55e,#facc15);border-radius:20px;z-index:0;"></div>
                            <div style="position:absolute;width:100%;height:100%;padding:0 40px;top:0;left:0;box-sizing:border-box;z-index:1;">
                                <div style="position:absolute;top:17.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:34px;font-weight:900;color:#1e293b;letter-spacing:2px;text-transform:uppercase;line-height:1;width:100%;">CERTIFICATE OF EXCELLENCE</div>
                                <div style="position:absolute;top:30%;left:50%;transform:translateX(-50%);text-align:center;font-size:17px;color:#374151;font-weight:500;line-height:1.4;width:100%;">This is to certify that</div>
                                <div style="position:absolute;top:45%;left:50%;transform:translateX(-50%);text-align:center;font-size:36px;font-weight:900;color:#1e293b;letter-spacing:1px;line-height:1.1;width:100%;">${certData.name}</div>
                                <div style="position:absolute;top:57.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;">
                                    has successfully completed the requirements for <strong>${certData.course}</strong> at <strong>${certData.grade}%</strong> classified as
                                </div>
                                <div style="position:absolute;top:70%;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;padding:13px 36px;border-radius:30px;font-size:21px;font-weight:900;box-shadow:0 6px 20px rgba(220,38,38,0.4);letter-spacing:1px;text-transform:uppercase;">${honorText}</div>
                                <div style="position:absolute;top:87.5%;left:50%;transform:translateX(-50%);text-align:center;font-size:15px;line-height:1.5;color:#374151;max-width:620px;width:100%;">
                                    Issued on this day, <strong>${new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}</strong>, to acknowledge exceptional commitment to learning and personal growth.
                                </div>
                            </div>
                        </div>`;

                    preview.innerHTML += '<div style="text-align:center;margin-top:20px;color:#059669;font-weight:bold;">✅ PNG Downloaded Successfully</div>';
                }, 'image/png', 0.95);

            }catch(err){
                preview.innerHTML = '<div class="loading">Export failed - try again</div>';
                console.error('Export error:', err);
            }
        }

        window.onclick = function(e){
            if(e.target.id === 'certModal') hideCert();
        };
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape') hideCert();
        });
    </script>

</body>
</html>

