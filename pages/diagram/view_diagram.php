<?php
$allData = $obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL  ORDER BY ag_id DESC;");
?>
<style>
    #context-menu {
        display: none;
        position: absolute;
        z-index: 1000;
        background: #fff;
        border: 1px solid #ccc;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    /* Tooltip container */
    #nodeDetails {
        display: none;
        position: absolute;
        background-color: #2b3e50;
        /* Dark background color */
        color: white;
        /* Text color */
        padding: 10px;
        /* Padding around the text */
        border-radius: 8px;
        /* Rounded corners */
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        /* Subtle shadow */
        font-family: Arial, sans-serif;
        /* Font style */
        font-size: 14px;
        /* Font size */
        line-height: 1.5;
        /* Line height for better readability */
        max-width: 200px;
        /* Maximum width of the tooltip */
        z-index: 1000;
        /* Ensure the tooltip is above other elements */
    }

    #nodeDetails::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 100%;
        margin-left: -5px;
        transform: translateY(-50%);
        border-width: 5px;
        border-style: solid;
        border-color: transparent transparent transparent #2b3e50;
    }


    #context-menu button {
        display: block;
        width: 100%;
        padding: 5px;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
    }

    #context-menu button:hover {
        background: #f0f0f0;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        width: 30%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 2rem;
        border-radius: 8px;
        min-width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }

    .modal-title {
        margin: 0;
        font-size: 1.25rem;
        color: #333;
    }

    .modal-body {
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #555;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .button {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .close {
        cursor: pointer;
        font-size: 1.5rem;
        color: #666;
    }
</style>
<div>
    <div id="chart" style="overflow-x: auto;" class="p-5"></div>
    <div id="context-menu">
        <button class="button" id="addNodeModal">Add Connection</button>
        <button class="button" id="deleteNode">Remove Node</button>
        <button class="button" id="addItemModal">Add Item</button>
        <button class="button" id="addUserModal">Add User</button>
        <button class="button" id="deleteUser">Remove User</button>
    </div>
    <div id="nodeDetails">
    </div>
    <!-- Node Modal -->
    <div id="connectionModal" class="modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fs-5" id="exampleModalLabel">Add Connection</h3>
                <!-- <span class="close" id="close-modal" onclick="closeModal('connectionModal')">&times;</span> -->
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="newNodeNameInput" class="form-label">New Node Name:</label>
                            <input type="text" id="name" class="form-control" placeholder="Enter new node name">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">description:</label>
                            <input type="text" name="desc" id="description" class="form-control" placeholder="Enter Description">
                        </div>
                        <input type="hidden" id="nodeId">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success waves-effect waves-light" id="addNode" onclick="addNode()">Connect</button>
                <button class="btn-secondary waves-effect" id="close-modal" onclick="closeModal('connectionModal')">Cancel</button>
            </div>
        </div>
    </div>
    <div id="userModal" class="modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fs-5" id="exampleModalLabel">Add User</h3>
                <!-- <span class="close" id="close-modal" onclick="closeModal('connectionModal')">&times;</span> -->
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="selectUser" class="form-label">User Name:</label>
                            <select class="form-control" id="users">
                                <option value="">Select User</option>
                                <?php foreach ($allData as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['ag_id']); ?>">
                                        <?php echo htmlspecialchars($user['ag_name']) . " (" . htmlspecialchars($user['ag_mobile_no']) . ")"; ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="user_desc" class="form-label">description:</label>
                            <input type="text" class="form-control" name="desc" id="user_desc" placeholder="Enter Description">
                        </div>
                    </div>
                </div>

                <input type="hidden" id="nodeId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary waves-effect" id="close-user-modal">Cancel</button>
                <button class="btn btn-success waves-effect waves-light" id="addUser">Connect</button>
            </div>
        </div>
    </div>

    <!-- Item Modal -->
    <div class="modal" id="itemModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <!-- <div class="modal-dialog"> -->
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Add Item</h1>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" name="subzone_id" id="subzone_id">
                        <div class="mb-3">
                            <label for="newItemNameInput" class="form-label">New Item Name:</label>
                            <input type="text" id="item_name" class="form-control" placeholder="Enter new Item name">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">description:</label>
                            <input type="text" id="item_desc" class="form-control" placeholder="Enter Description">
                        </div>
                        <input type="hidden" id="nodeId">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" id="close-modal-item">Cancel</button>
                    <button type="button" id="addItem" class="btn btn-success waves-effect waves-light">Add</button>
                </div>
            </div>
            <!-- </div> -->
        </div>
    </div>


    <?php $obj->start_script(); ?>

    <script>
        $(document).ready(function() {
            $('.select').select2({
                placeholder: "Select a user",
                allowClear: true
            });
        });
        fetch("./pages/diagram/addNode.php")
            .then(function(resp) {
                return resp.json();
            })
            .then(function(data) {
                console.log(data);
                parentFunction(data);
            });

        function parentFunction(data) {
            const chart = () => {
                const sidebarWidth = document.getElementById('sidebar').clientWidth;
                const fullWidth = document.body.clientWidth;
                const width = fullWidth - sidebarWidth;
                // const padding = 50; // Add padding around the SVG

                const marginTop = 10;
                const marginRight = 10;
                const marginBottom = 10;
                const marginLeft = 40;

                const root = d3.hierarchy(data);
                const dx = 40;
                const dy = (width - marginRight - marginLeft) / (1 + root.height);
                const tree = d3.tree().nodeSize([dx, dy]);
                const diagonal = d3
                    .linkHorizontal()
                    .x((d) => d.y)
                    .y((d) => d.x);

                tree(root);

                // Calculate the height based on the tree layout
                let x0 = Infinity;
                let x1 = -x0;
                root.each((d) => {
                    if (d.x > x1) x1 = d.x;
                    if (d.x < x0) x0 = d.x;
                });

                const svgHeight = x1 - x0 + marginTop + marginBottom;

                // Center the tree horizontally
                // const centerX = (width - (root.height + 1) * dy) / 2;

                const svg = d3
                    .create("svg")
                    .attr("width", width)
                    .attr("height", svgHeight)
                    .attr("viewBox", [-marginLeft, -marginTop, width, svgHeight])
                    .attr(
                        "style",
                        "max-width: auto; height: 100vh; font: 10px sans-serif; user-select: none;"
                    );

                const gLink = svg
                    .append("g")
                    .attr("fill", "none")
                    .attr("stroke", "#555")
                    .attr("stroke-opacity", 0.8)
                    .attr("stroke-width", 1.5);

                const gNode = svg
                    .append("g")
                    .attr("cursor", "pointer")
                    .attr("pointer-events", "all");

                function update(event, source) {
                    const duration = event?.altKey ? 2500 : 250;
                    const nodes = root.descendants().reverse();
                    const links = root.links();

                    tree(root);

                    let left = root;
                    let right = root;
                    root.eachBefore((node) => {
                        if (node.x < left.x) left = node;
                        if (node.x > right.x) right = node;
                    });

                    const height = right.x - left.x + marginTop + marginBottom;

                    const transition = svg
                        .transition()
                        .duration(duration)
                        .attr("height", height)
                        .attr("viewBox", [-marginLeft, left.x - marginTop, width, height])
                        .tween(
                            "resize",
                            window.ResizeObserver ? null : () => () => svg.dispatch("toggle")
                        );

                    const node = gNode.selectAll("g").data(nodes, (d) => d.id);

                    const nodeEnter = node
                        .enter()
                        .append("g")
                        .attr("transform", (d) => `translate(${source.y0},${source.x0})`)
                        .attr("fill-opacity", 0)
                        .attr("stroke-opacity", 0)
                        .on("click", (event, d) => {
                            d.children = d.children ? null : d._children;
                            update(event, d);
                        })
                        .on("contextmenu", (event, d) => {
                            event.preventDefault();
                            showContextMenu(event.pageX, event.pageY, d);
                        })
                        .on("mouseover", (event, d) => {
                            let childLength;
                            if (d.data && d.data.children && Array.isArray(d.data.children)) {
                                childLength = d.data.children.length;
                            } else {
                                childLength = "N/A";
                            }
                            const menu = document.getElementById("nodeDetails");
                            menu.style.display = "block";
                            menu.style.left = `${event.pageX}px`;
                            menu.style.top = `${event.pageY}px`;




                            //  fetch item value

                            fetch("./pages/diagram/showDetails.php?id=" + d.data.id)
                                .then(function(resp) {
                                    return resp.json();
                                })
                                .then(function(data) {
                                    fetchData(data);

                                });

                            function fetchData(data) {


                                const ul = document.createElement('ul');
                                data.forEach(function(item) {
                                    const list = document.createElement('li');
                                    list.textContent = item.name;
                                    ul.appendChild(list);
                                })

                                menu.innerHTML = `
              <div><strong>Name:</strong> ${d.data.name}</div>
      <div><strong>Direct Child:</strong> ${childLength}</div>
      <div><strong>Assigned Items:</strong> ${ul.outerHTML}</div>
      <hr>`
                            }


                        })
                        .on("mouseout", (event, d) => {
                            const menu = document.getElementById("nodeDetails");
                            menu.style.display = "none";
                        });
                    nodeEnter
                        .append("circle")
                        .attr("r", 12)
                        .attr("fill", (d) => {
                            if (d.data.user_id) {
                                return "#00BCDA"; // Replace with the desired color for user_id
                            } else if (d._children) {
                                return "#008000";
                            } else {
                                return "#581845"; // Default color if none of the conditions are met
                            }
                        })
                        .attr("stroke-width", 10);

                    nodeEnter
                        .append("text")
                        .attr("dy", "0.31em")
                        .attr("x", (d) => (d._children ? 0 : 15))
                        .attr("y", (d) => (d._children ? -15 : 0))
                        .attr("text-anchor", (d) => (d._children ? "end" : "start"))
                        .text((d) => d.data.name)
                        .attr("stroke-linejoin", "round")
                        .attr("stroke-width", 3)
                        .attr("stroke", "white")
                        .attr("paint-order", "stroke")
                        .style("font-size", "20px");

                    const nodeUpdate = node
                        .merge(nodeEnter)
                        .transition(transition)
                        .attr("transform", (d) => `translate(${d.y},${d.x})`)
                        .attr("fill-opacity", 1)
                        .attr("stroke-opacity", 1);

                    const nodeExit = node
                        .exit()
                        .transition(transition)
                        .remove()
                        .attr("transform", (d) => `translate(${source.y},${source.x})`)
                        .attr("fill-opacity", 0)
                        .attr("stroke-opacity", 0);

                    const link = gLink.selectAll("path").data(links, (d) => d.target.id);

                    const linkEnter = link
                        .enter()
                        .append("path")
                        .attr("d", (d) => {
                            const o = {
                                x: source.x0,
                                y: source.y0
                            };
                            return diagonal({
                                source: o,
                                target: o
                            });
                        });

                    link.merge(linkEnter).transition(transition).attr("d", diagonal);

                    link
                        .exit()
                        .transition(transition)
                        .remove()
                        .attr("d", (d) => {
                            const o = {
                                x: source.x,
                                y: source.y
                            };
                            return diagonal({
                                source: o,
                                target: o
                            });
                        });

                    root.eachBefore((d) => {
                        d.x0 = d.x;
                        d.y0 = d.y;
                    });
                }

                root.x0 = dy / 2;
                root.y0 = 0;
                root.descendants().forEach((d, i) => {
                    d.id = i;
                    d._children = d.children;

                    // Collapse nodes beyond depth 2
                    if (d.depth > 4) {
                        d.children = null;
                    }
                });

                update(null, root);

                return svg.node();
            };

            function showContextMenu(x, y, nodeData) {
                // console.log(nodeData);

                const menu = document.getElementById("context-menu");

                // Check if user_id is null
                if (nodeData.data.user_id == null) {
                    // If user_id is null, show the context menu and only show addItemModal
                    menu.style.display = "block";
                    menu.style.left = `${x}px`;
                    menu.style.top = `${y}px`;

                    // Hide all modals first
                    document.getElementById("addNodeModal").style.display = "block";
                    document.getElementById("addUserModal").style.display = "block";

                    document.getElementById("deleteUser").style.display = "none";

                    document.getElementById("addNodeModal").onclick = () => {
                        showModal(nodeData);
                        menu.style.display = "none";
                    };
                    document.getElementById("addUserModal").onclick = () => {
                        showUserModal(nodeData);
                        menu.style.display = "none";
                    };

                    // Delete Node Function
                    const deleteNode = document.getElementById("deleteNode");
                    deleteNode.style.display = "block";

                    deleteNode.onclick = () => {
                        if (nodeData.children && nodeData.children.length > 0) {
                            // Show Sweet Alert if the node has children
                            Swal.fire({
                                icon: "error",
                                title: "Cannot Delete",
                                text: "This node has children and cannot be deleted.",
                            });
                        } else {
                            // Show confirmation dialog
                            Swal.fire({
                                title: "Are you sure?",
                                text: "You won't be able to revert this!",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "Yes, delete it!"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Call delete function here
                                    deleteNodeConn(nodeData);
                                    menu.style.display = "none";
                                    Swal.fire(
                                        "Deleted!",
                                        "The node has been deleted.",
                                        "success"
                                    );
                                }
                            });
                        }
                    }

                } else {
                    // If user_id is not null, hide addNodeModal and addUserModal, and show addItemModal
                    menu.style.display = "block";
                    menu.style.left = `${x}px`;
                    menu.style.top = `${y}px`;

                    document.getElementById("addNodeModal").style.display = "none";
                    document.getElementById("addUserModal").style.display = "none";
                    document.getElementById("deleteNode").style.display = "none";

                    // Delete Node Function
                    // const deleteNode = document.getElementById("deleteUser");
                    document.getElementById("deleteUser").style.display = "block";

                    document.getElementById("deleteUser").onclick = () => {
                        if (nodeData.children && nodeData.children.length > 0) {
                            // Show Sweet Alert if the node has children
                            Swal.fire({
                                icon: "error",
                                title: "Cannot Delete",
                                text: "This User Node has children and cannot be deleted.",
                            });
                        } else {
                            // Show confirmation dialog
                            Swal.fire({
                                title: "Are you sure?",
                                text: "You won't be able to revert this!",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "Yes, delete it!"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Call delete function here
                                    deleteNodeConn(nodeData);
                                    menu.style.display = "none";
                                    Swal.fire(
                                        "Deleted!",
                                        "The User Node has been deleted.",
                                        "success"
                                    );
                                }
                            });
                        }
                    }


                }

                document.getElementById("addItemModal").style.display = "block"; // Show only addItemModal

                document.getElementById("addItemModal").onclick = () => {
                    showItemModal(nodeData);
                    menu.style.display = "none";
                };


                // Close the menu when clicked elsewhere
                document.addEventListener("click", closeContextMenu, {
                    once: true
                });
            }

            async function deleteNodeConn(nodeData) {
                const nodeId = nodeData.data.id; // Get the ID of the node to delete

                // const newNode = {
                //     id: nodeId,
                // };

                try {
                    // Send a DELETE request to the server
                    const response = await fetch('./pages/diagram/addNode.php', {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            id: nodeId
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    const message = result.message;

                    if (result) {
                        // Swal.fire({
                        //     position: "top-end",
                        //     icon: "success",
                        //     title: message || "Node deleted successfully!",
                        //     showConfirmButton: false,
                        //     timer: 1500,
                        // });

                        // Fetch updated data after deletion
                        const resp = await fetch("./pages/diagram/addNode.php");
                        const data = await resp.json();
                        document.getElementById("chart").innerHTML = ""; // Clear the existing chart
                        parentFunction(data); // Redraw the chart with updated data
                    } else {
                        throw new Error(message || "Failed to delete the node.");
                    }
                } catch (err) {
                    console.error("Error deleting node:", err);

                    // Show error alert
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Could not delete the node. Please try again later.",
                    });
                }
            }



            function showDetails(x, y, nodeData) {
                const menu = document.getElementById("nodeDetails");

                menu.onmouseover = () => {
                    menu.style.display = "block";
                    menu.style.left = `${x}px`;
                    menu.style.top = `${y}px`;
                    menu.style.display = "block";
                };
            }

            function closeContextMenu() {
                const menu = document.getElementById("context-menu");
                menu.style.display = "none";
            }

            function showUserModal(nodeData) {
                const modal = document.getElementById("userModal");
                modal.style.display = "block";
                document.getElementById("addUser").onclick = () => {
                    addUser(nodeData);
                };

                document.getElementById("close-user-modal").onclick = closeUserModal;
                modal.onclick = (event) => {
                    if (event.target === modal) closeUserModal();
                };
            }

            function showModal(nodeData) {
                const modal = document.getElementById("connectionModal");
                modal.style.display = "block";
                document.getElementById("addNode").onclick = () => {
                    addNode(nodeData);
                };

                document.getElementById("close-modal").onclick = closeModal;
                modal.onclick = (event) => {
                    if (event.target === modal) closeModal();
                };
            }

            function showItemModal(nodeData) {
                const modal = document.getElementById("itemModal");
                modal.style.display = "block";
                document.getElementById("addItem").onclick = () => {
                    addItem(nodeData);
                };

                document.getElementById("close-modal-item").onclick = closeItemModal;
                modal.onclick = (event) => {
                    if (event.target === modal) closeItemModal();
                };
            }

            function closeUserModal() {
                const modal = document.getElementById("userModal");

                let nodeName = document.getElementById("name");
                let nodeDescription = document.getElementById("description");

                nodeName.value = "";
                nodeDescription.value = "";
                modal.style.display = "none";
            }

            function closeModal() {
                const modal = document.getElementById("connectionModal");

                let nodeName = document.getElementById("name");
                let nodeDescription = document.getElementById("description");

                nodeName.value = "";
                nodeDescription.value = "";
                modal.style.display = "none";
            }

            function closeItemModal() {
                const modal = document.getElementById("itemModal");

                let nodeName = document.getElementById("item_name");
                let nodeDescription = document.getElementById("item_desc");

                nodeName.value = "";
                nodeDescription.value = "";
                modal.style.display = "none";
            }
            async function addNode(nodeData) {
                let nodeName = document.getElementById("name").value.trim();
                let nodeDescription = document.getElementById("description").value.trim();
                const node = document.getElementById("nodeId");
                node.value = nodeData.data.id;
                const newNode = {

                    name: nodeName,
                    description: nodeDescription,
                    parent_id: node.value,
                };

                try {
                    const response = await fetch("./pages/diagram/addNode.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(newNode),
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    const message = result.message;
                    if (result) {
                        closeModal();

                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: message,
                            showConfirmButton: false,
                            timer: 1500,
                        });
                        const resp = await fetch("./pages/diagram/addNode.php");
                        const data = await resp.json();
                        document.getElementById("chart").innerHTML = "";
                        // Redraw the chart with the updated data
                        parentFunction(data);
                    }

                    return result;
                } catch (err) {
                    console.error("Error adding node:", err);
                    throw err;
                }
            }

            async function addUser(nodeData) {
                var user = document.getElementById("users");
                var user_id = user.options[user.selectedIndex].value;
                var user_name = user.options[user.selectedIndex].text;


                // let nodeName = document.getElementById("name").value.trim();
                let nodeDescription = document.getElementById("user_desc").value.trim();
                const node = document.getElementById("nodeId");
                node.value = nodeData.data.id;
                const newNode = {
                    parent_id: node.value,
                    name: user_name,
                    user_id: user_id,
                    description: nodeDescription,
                };

                try {
                    const response = await fetch("./pages/diagram/addNode.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(newNode),
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    const message = result.message;
                    if (result) {
                        closeUserModal();
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: message,
                            showConfirmButton: false,
                            timer: 1500,
                        });

                        const resp = await fetch("./pages/diagram/addNode.php");
                        const data = await resp.json();
                        document.getElementById("chart").innerHTML = "";
                        // Redraw the chart with the updated data
                        parentFunction(data);
                    }

                    return result;
                } catch (err) {
                    console.error("Error adding User:", err);
                    throw err;
                }
            }
            async function addItem(nodeData) {
                let itemName = document.getElementById("item_name").value.trim();
                let itemDescription = document.getElementById("item_desc").value.trim();
                const node = document.getElementById("nodeId");
                node.value = nodeData.data.id;
                const newNode = {
                    node_id: node.value,
                    name: itemName,
                    description: itemDescription,
                };

                try {
                    const response = await fetch("./pages/diagram/item.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(newNode),
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    const message = result.message;
                    if (result) {
                        closeItemModal();
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: message,
                            showConfirmButton: false,
                            timer: 1500,
                        });

                        const resp = await fetch("./pages/diagram/addNode.php");
                        const data = await resp.json();
                        document.getElementById("chart").innerHTML = "";
                        // Redraw the chart with the updated data
                        parentFunction(data);
                    }

                    return result;
                } catch (err) {
                    console.error("Error adding item:", err);
                    throw err;
                }
            }

            document.getElementById("chart").appendChild(chart());
        }
    </script>
    <?php $obj->end_script() ?>