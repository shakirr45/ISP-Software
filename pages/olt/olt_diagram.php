<?php
// --- PHP Backend: Data Collection from OLT ---

set_time_limit(300); // Increase execution time for SNMP

// Configuration
$oltIp = "172.35.156.14";
$community = "bsd";

// SNMP OIDs
$oidIfDescr      = "1.3.6.1.2.1.2.2.1.2";
$oidRxPower      = "1.3.6.1.4.1.3320.101.10.5.1.5";
$oidTxPower      = "1.3.6.1.4.1.3320.101.10.5.1.6";
$oidIfOperStatus = "1.3.6.1.2.1.2.2.1.8";

// Execute SNMP Commands
$descrOutput   = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidIfDescr}");
$rxPowerOutput = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidRxPower}");
$txPowerOutput = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidTxPower}");
$statusOutput  = shell_exec("snmpwalk -v2c -c {$community} {$oltIp} {$oidIfOperStatus}");

$interfaceData = [];

// Parse Interface Descriptions
foreach (explode("\n", trim($descrOutput)) as $line) {
    if (preg_match('/\.(\d+) = STRING: (.+)/', $line, $m)) {
        $interfaceData[$m[1]]['name'] = trim($m[2]);
    }
}

// Parse Rx Power
foreach (explode("\n", trim($rxPowerOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: (-?\d+)/', $line, $m) && $m[2] != -65535) {
        $interfaceData[$m[1]]['rx_power'] = $m[2] / 10;
    }
}

// Parse Tx Power
foreach (explode("\n", trim($txPowerOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: (-?\d+)/', $line, $m) && $m[2] != -65535) {
        $interfaceData[$m[1]]['tx_power'] = $m[2] / 10;
    }
}

// Parse Interface Status
foreach (explode("\n", trim($statusOutput)) as $line) {
    if (preg_match('/\.(\d+) = INTEGER: \w+\((\d+)\)/', $line, $matches)) {
        $index = $matches[1];
        $statusCode = $matches[2];
        $statusMap = [
            1 => 'Connected', 2 => 'Down', 3 => 'Testing', 4 => 'Unknown',
            5 => 'Dormant', 6 => 'Not Present', 7 => 'Lower Layer Down'
        ];
        $interfaceData[$index]['status'] = $statusMap[$statusCode] ?? 'Unknown';
    }
}

// Build EPON Tree for the diagram
$eponTree = [];
foreach ($interfaceData as $index => $item) {
    $name = $item['name'] ?? '';
    if (preg_match('/^EPON0\/(\d+):(\d+)$/', $name, $match)) {
        $port = "EPON0/" . $match[1];
        $eponTree[$port]['onus'][] = [
            'name'     => $name,
            'rx_power' => $item['rx_power'] ?? null,
            'tx_power' => $item['tx_power'] ?? null,
            'status'   => $item['status'] ?? 'Unknown',
        ];
    } elseif (preg_match('/^EPON0\/(\d+)$/', $name)) {
        // Ensure port exists in the tree even if it has no ONUs
        if (!isset($eponTree[$name])) {
            $eponTree[$name]['onus'] = [];
        }
    }
}
ksort($eponTree); // Sort ports numerically

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>OLT to ONU Network Topology Diagram</title>
    <style>
        /* Compact and Refined CSS */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #f5f7fa; padding: 10px; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; font-size: 1.8em; margin-bottom: 5px; }
        .network-diagram { position: relative; min-height: 600px; background: #fafbfc; border: 1px solid #e1e8ed; border-radius: 10px; padding: 20px; overflow: hidden; }
        .device { position: absolute; display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: transform 0.2s ease; }
        .device:hover { transform: scale(1.1); }
        .device-icon { width: 50px; height: 35px; background: #3498db; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; margin-bottom: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); position: relative; }
        .olt-icon { background: linear-gradient(145deg, #2c3e50, #34495e); width: 80px; height: 50px; }
        .epon-icon { background: linear-gradient(145deg, #e74c3c, #c0392b); width: 60px; height: 40px; }
        .onu-icon { background: linear-gradient(145deg, #27ae60, #2ecc71); width: 40px; height: 30px; font-size: 14px; }
        .device-label { font-size: 10px; font-weight: bold; color: #2c3e50; text-align: center; max-width: 100px; word-wrap: break-word; }
        .device-info { font-size: 9px; color: #7f8c8d; text-align: center; margin-top: 2px; max-width: 100px; }
        .connection-line { position: absolute; background: #95a5a6; z-index: 1; }
        .connection-line.active { background: #27ae60; box-shadow: 0 0 5px rgba(39, 174, 96, 0.6); }
        .connection-line.inactive { background: #e74c3c; box-shadow: 0 0 5px rgba(231, 76, 60, 0.6); }
        .horizontal-line { height: 2px; }
        .vertical-line { width: 2px; }
        .status-dot { position: absolute; top: -3px; right: -3px; width: 10px; height: 10px; border-radius: 50%; border: 1px solid white; }
        .status-connected { background: #27ae60; animation: pulse-green 2s infinite; }
        .status-down { background: #e74c3c; }
        .status-other { background: #f39c12; }
        @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(39, 174, 96, 0); } 100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0); } }
        .legend { position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.9); padding: 10px; border-radius: 6px; border: 1px solid #ddd; font-size: 10px; z-index: 10; }
        .legend-item { display: flex; align-items: center; margin-bottom: 5px; }
        .legend-icon { width: 15px; height: 10px; margin-right: 5px; border-radius: 2px; }
        .info-panel { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #3498db; border-radius: 8px; padding: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: none; z-index: 1000; min-width: 250px; }
        .info-panel h3 { color: #2c3e50; margin-bottom: 10px; text-align: center; font-size: 1em; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; padding: 3px 0; border-bottom: 1px solid #ecf0f1; font-size: 0.8em; }
        .close-btn { position: absolute; top: 5px; right: 10px; background: none; border: none; font-size: 16px; cursor: pointer; color: #7f8c8d; }
        @media (max-width: 1200px) { .network-diagram { min-height: 400px; overflow-x: auto; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌐 OLT Network Topology Diagram</h1>
            <p>IP: <strong><?php echo htmlspecialchars($oltIp); ?></strong> | Community: <strong><?php echo htmlspecialchars($community); ?></strong></p>
        </div>

        <div class="network-diagram" id="networkDiagram">
            <div class="legend">
                <div class="legend-item"><div class="legend-icon" style="background: #2c3e50;"></div><span>OLT</span></div>
                <div class="legend-item"><div class="legend-icon" style="background: #e74c3c;"></div><span>EPON Port</span></div>
                <div class="legend-item"><div class="legend-icon" style="background: #27ae60;"></div><span>ONU</span></div>
                <div class="legend-item"><div class="status-dot status-connected" style="position: relative; top: 0; right: 0; margin-right: 8px;"></div><span>Connected</span></div>
                <div class="legend-item"><div class="status-dot status-down" style="position: relative; top: 0; right: 0; margin-right: 8px;"></div><span>Disconnected</span></div>
            </div>
        </div>

        <div class="info-panel" id="infoPanel">
            <button class="close-btn" onclick="closeInfoPanel()">×</button>
            <h3 id="infoTitle">Device Information</h3>
            <div id="infoContent"></div>
        </div>
    </div>

    <script>
        // --- JavaScript Frontend: Rendering the Diagram ---
        const networkData = <?php echo json_encode($eponTree); ?>;

        function getStatusClass(status) {
            if (!status) return 'other';
            const statusLower = status.toLowerCase();
            if (statusLower === 'connected') return 'connected';
            if (statusLower === 'down') return 'down';
            return 'other';
        }

        function createDevice(type, label, x, y, info = {}) {
            const device = document.createElement('div');
            device.className = 'device';
            device.style.left = x + 'px';
            device.style.top = y + 'px';
            
            const icon = document.createElement('div');
            icon.className = `device-icon ${type}-icon`;
            
            const symbols = { 'olt': '🏢', 'epon': '🔌', 'onu': '📡' };
            icon.innerHTML = symbols[type] || '📱';
            
            if (type === 'onu' && info.status) {
                const statusDot = document.createElement('div');
                statusDot.className = `status-dot status-${getStatusClass(info.status)}`;
                icon.appendChild(statusDot);
            }
            
            const labelEl = document.createElement('div');
            labelEl.className = 'device-label';
            labelEl.textContent = label;
            
            const infoEl = document.createElement('div');
            infoEl.className = 'device-info';
            if (info.rx_power && info.tx_power) {
                infoEl.innerHTML = `RX: ${info.rx_power} dBm<br>TX: ${info.tx_power} dBm`;
            } else if (info.status) {
                infoEl.textContent = info.status;
            }
            
            device.appendChild(icon);
            device.appendChild(labelEl);
            device.appendChild(infoEl);
            
            device.onclick = () => showDeviceInfo(type, label, info);
            
            return device;
        }

        function createConnectionLine(x1, y1, x2, y2, isActive = true) {
            const lines = [];
            const diagram = document.getElementById('networkDiagram');
            
            const vLine1 = document.createElement('div');
            vLine1.className = `connection-line vertical-line ${isActive ? 'active' : 'inactive'}`;
            vLine1.style.left = (x1 + 40) + 'px';
            vLine1.style.top = (y1 + 50) + 'px';
            vLine1.style.height = (y2 - (y1 + 50)) + 'px';
            lines.push(vLine1);
            
            return lines;
        }
        
        function createConnectionElbow(x1, y1, x2, y2, isActive = true) {
            const lines = [];
            const elbowX = x1 + 30;
            const elbowY = y2 - 25;
            
            const vLine1 = document.createElement('div');
            vLine1.className = `connection-line vertical-line ${isActive ? 'active' : 'inactive'}`;
            vLine1.style.left = elbowX + 'px';
            vLine1.style.top = (y1 + 40) + 'px';
            vLine1.style.height = (elbowY - (y1 + 40)) + 'px';
            lines.push(vLine1);

            const hLine = document.createElement('div');
            hLine.className = `connection-line horizontal-line ${isActive ? 'active' : 'inactive'}`;
            hLine.style.left = Math.min(elbowX, x2 + 20) + 'px';
            hLine.style.top = elbowY + 'px';
            hLine.style.width = Math.abs(elbowX - (x2 + 20)) + 'px';
            lines.push(hLine);
            
            const vLine2 = document.createElement('div');
            vLine2.className = `connection-line vertical-line ${isActive ? 'active' : 'inactive'}`;
            vLine2.style.left = (x2 + 20) + 'px';
            vLine2.style.top = elbowY + 'px';
            vLine2.style.height = (y2 - elbowY) + 'px';
            lines.push(vLine2);

            return lines;
        }

        function renderNetworkDiagram() {
            const diagram = document.getElementById('networkDiagram');
            const legend = diagram.querySelector('.legend');
            diagram.innerHTML = '';
            diagram.appendChild(legend);
            
            const diagramWidth = diagram.clientWidth;
            const diagramHeight = diagram.clientHeight;

            const oltX = (diagramWidth / 2) - 40;
            const oltY = 20;
            const olt = createDevice('olt', 'OLT Main Node', oltX, oltY, {});
            diagram.appendChild(olt);

            const ports = Object.keys(networkData);
            const portCount = ports.length;
            const portSpacing = 150;
            const portContainerWidth = portCount * portSpacing;
            let startX = (diagramWidth - portContainerWidth) / 2;
            if (startX < 10) startX = 10;

            let currentY = 150;

            ports.forEach((portName, portIndex) => {
                const portX = startX + portIndex * portSpacing;
                const epon = createDevice('epon', portName, portX, currentY, {});
                diagram.appendChild(epon);
                
                const oltLines = createConnectionLine(oltX, oltY, portX, currentY, true);
                oltLines.forEach(line => diagram.insertBefore(line, olt));
                
                const onus = networkData[portName].onus || [];
                if (onus.length > 0) {
                    const columnWidth = 80;
                    const rowHeight = 70;
                    const numColumns = 3;
                    
                    let onuY = currentY + 100;
                    
                    onus.forEach((onu, onuIndex) => {
                        const col = onuIndex % numColumns;
                        const row = Math.floor(onuIndex / numColumns);
                        
                        const onuX = portX - 30 + (col * columnWidth);
                        const finalOnuY = onuY + (row * rowHeight);

                        const isConnected = onu.status && onu.status.toLowerCase() === 'connected';
                        const onuDevice = createDevice('onu', onu.name, onuX, finalOnuY, onu);
                        diagram.appendChild(onuDevice);
                        
                        const onuLines = createConnectionElbow(portX, currentY, onuX, finalOnuY, isConnected);
                        onuLines.forEach(line => diagram.insertBefore(line, epon));
                    });

                    const lastOnuRow = Math.floor((onus.length - 1) / numColumns);
                    const maxOnuY = onuY + (lastOnuRow * rowHeight) + 80;
                    if (maxOnuY > diagramHeight) {
                        diagram.style.minHeight = `${maxOnuY}px`;
                    }
                }
            });
        }

        function showDeviceInfo(type, label, info) {
            const panel = document.getElementById('infoPanel');
            const title = document.getElementById('infoTitle');
            const content = document.getElementById('infoContent');
            const oltIp = "<?php echo htmlspecialchars($oltIp); ?>";
            const community = "<?php echo htmlspecialchars($community); ?>";

            title.textContent = `${type.toUpperCase()}: ${label}`;
            
            let infoHtml = '';
            if (type === 'olt') {
                infoHtml = `
                    <div class="info-row"><span>IP Address:</span><span>${oltIp}</span></div>
                    <div class="info-row"><span>Community:</span><span>${community}</span></div>
                    <div class="info-row"><span>Type:</span><span>Optical Line Terminal</span></div>`;
            } else if (type === 'epon') {
                const onuCount = networkData[label]?.onus?.length || 0;
                const connectedCount = networkData[label]?.onus?.filter(onu => onu.status && onu.status.toLowerCase() === 'connected').length || 0;
                infoHtml = `
                    <div class="info-row"><span>Port:</span><span>${label}</span></div>
                    <div class="info-row"><span>Total ONUs:</span><span>${onuCount}</span></div>
                    <div class="info-row"><span>Connected ONUs:</span><span>${connectedCount}</span></div>
                    <div class="info-row"><span>Type:</span><span>Ethernet PON Port</span></div>`;
            } else if (type === 'onu') {
                infoHtml = `
                    <div class="info-row"><span>ONU ID:</span><span>${label}</span></div>
                    <div class="info-row"><span>Status:</span><span>${info.status || 'Unknown'}</span></div>
                    <div class="info-row"><span>RX Power:</span><span>${info.rx_power ? info.rx_power + ' dBm' : 'N/A'}</span></div>
                    <div class="info-row"><span>TX Power:</span><span>${info.tx_power ? info.tx_power + ' dBm' : 'N/A'}</span></div>
                    <div class="info-row"><span>Type:</span><span>Optical Network Unit</span></div>`;
            }
            
            content.innerHTML = infoHtml;
            panel.style.display = 'block';
        }

        function closeInfoPanel() {
            document.getElementById('infoPanel').style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            const panel = document.getElementById('infoPanel');
            if (panel.style.display === 'block' && !panel.contains(e.target) && !e.target.closest('.device')) {
                closeInfoPanel();
            }
        });

        renderNetworkDiagram();
        window.onresize = renderNetworkDiagram;
    </script>
</body>
</html>