<<<<<<< HEAD
<?php
if (isset($_POST['serie_id'])) {
    $serieNombre = htmlspecialchars($_POST['serie_id']); // Obtener el nombre de la serie
    require "conexion3.php";

    if ($conec->connect_error) {
        die(json_encode(['error' => 'Error de conexión: ' . $conec->connect_error]));
    }

    try {
        $query = $conec->prepare("SELECT id, Subs AS nombre FROM Subs WHERE serie_nombre = ?");
        if (!$query) {
            die(json_encode(['error' => 'Error en la preparación de la consulta: ' . $conec->error]));
        }

        $query->bind_param('s', $serieNombre);
        $query->execute();
        $result = $query->get_result();

        $subseries = [];
        while ($row = $result->fetch_assoc()) {
            $subseries[] = $row;
        }

        echo json_encode($subseries);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al ejecutar la consulta: ' . $e->getMessage()]);
    } finally {
        if (isset($query)) {
            $query->close();
        }
        if (isset($conec)) {
            $conec->close();
        }
    }
} else {
    echo json_encode(['error' => 'ID de serie no recibido']);
}
?>
=======
<?php
if (isset($_POST['serie_id'])) {
    $serieNombre = htmlspecialchars($_POST['serie_id']); // Obtener el nombre de la serie
    require "conexion3.php";

    if ($conec->connect_error) {
        die(json_encode(['error' => 'Error de conexión: ' . $conec->connect_error]));
    }

    try {
        $query = $conec->prepare("SELECT id, Subs AS nombre FROM Subs WHERE serie_nombre = ?");
        if (!$query) {
            die(json_encode(['error' => 'Error en la preparación de la consulta: ' . $conec->error]));
        }

        $query->bind_param('s', $serieNombre);
        $query->execute();
        $result = $query->get_result();

        $subseries = [];
        while ($row = $result->fetch_assoc()) {
            $subseries[] = $row;
        }

        echo json_encode($subseries);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al ejecutar la consulta: ' . $e->getMessage()]);
    } finally {
        if (isset($query)) {
            $query->close();
        }
        if (isset($conec)) {
            $conec->close();
        }
    }
} else {
    echo json_encode(['error' => 'ID de serie no recibido']);
}
?>
>>>>>>> ee1e5fb4c792c180dd9273ba8cb2d81832fb03fc
