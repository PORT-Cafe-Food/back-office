<h1> Hello from devcontainer-example!</h1>

<p>It's not super exciting, but this is being served from the webserver
    container in our devcontainer.</p>

<h2>Guestbook</h2>

<table cellspacing="8">
    <tr>
        <th>Name</th>
        <th>Visited</th>
        <th>Note</th>
    <tr>

        <?php

        require_once('core/db.php');
        $sql = "SELECT * FROM Customers WHERE customer_id = 3";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            // output data of each row
            echo "Customers: <br>";
            // echo length($result);

            while ($row = $result->fetch_assoc()) {
                echo "id: " . $row["customer_id"] . " - Name: " . $row["fullname"] . " " . $row["address"] . "<br>";
            }
        } else {
            echo "0 results";
        }
        ?>
</table>