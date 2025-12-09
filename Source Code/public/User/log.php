<?php
include 'config.php';
$stmt = $pdo->query("
    SELECT temp, hum, light, device_time
    FROM sensor_log
    ORDER BY device_time DESC
    LIMIT 30
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- <script src="https://unpkg.com/paho-mqtt@1.1.0/paho-mqtt-min.js"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.1.0/paho-mqtt.min.js"></script> -->

    <!-- <script src="https://unpkg.com/paho-mqtt/mqttws31.min.js"></script> -->

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Smart Home <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="log.php">
                    <i class="fa-solid fa-list"></i>
                    <span>Lịch sử cảm biến</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
				<a class="nav-link collapsed" href="logout.php">
					<i class="btn btn-primary"></i>Đăng Xuất
				</a>
			</li>

            <!-- Heading -->
            <div class="sidebar-heading">
                Smart Home
            </div>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

            <!-- Sidebar Message -->

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div class="container mt-4">
                <h4 class="mb-3">
                    <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                    Lịch sử cảm biến
                </h4>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Nhiệt độ (°C)</th>
                                <th>Độ ẩm (%)</th>
                                <th>Ánh sáng</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody id="sensorLogTable">
                            <?php foreach ($logs as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= $row['temp'] ?></td>
                                    <td><?= $row['hum'] ?></td>
                                    <td><?= $row['light'] ?></td>
                                    <td><?= $row['device_time'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <!-- <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script> -->
    <!-- MQTT scripts -->
    <script src="js/mqttws31.min.js"></script>
    <script>
        console.log("Paho:", Paho);

        const host = "530052fe99b94418a3414955fddef258.s1.eu.hivemq.cloud";
        const port = 8884;
        const clientID = "webClient-" + Math.random().toString(16).substr(2, 8);

        const client = new Paho.Client(host, port, clientID);

        const options = {
            useSSL: true,
            userName: "smartHome",
            password: "Hieu@123456",
            onSuccess: onConnect,
            onFailure: (err) => console.log(" Connect failed", err)
        };

        client.connect(options);

        function onConnect() {
            console.log(" Connected");
            client.subscribe("smartHome/data");
        }

        client.onConnectionLost = function (e) {
            console.log(" Connection lost:", e.errorMessage);
            setTimeout(() => client.connect(options), 2000);
        };

        client.onMessageArrived = function (msg) {
            console.log("...", msg.payloadString);

            let data;
            try {
                data = JSON.parse(msg.payloadString);
                fetch('updateDevice.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            loadDevicesFromDB();
                        }
                    });



            }
            catch (e) { return console.error("JSON error:", e); }


        };
    </script>

    <script src="js/esp32.js"></script>
    <script src="js/main.js"></script>

</body>

</html>