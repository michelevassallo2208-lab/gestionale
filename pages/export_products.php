<?php
// pages/export_products.php

session_start();
require_once '../function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = connectDB();

        // Determina quale pulsante è stato premuto
        $isExportTotal = isset($_POST['export_total']);
        $isExportTable = isset($_POST['export_table']);

        if ($isExportTotal) {
            // Esporta tutti i prodotti
            $stmt = $db->prepare("
                SELECT p.name, p.description, p.model, p.manufacturer, s.name AS supplier_name,
                       p.quantity, p.availability, p.purchase_price, p.shelf, p.barcode
                FROM products p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                ORDER BY p.name
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $export_type = "Export Totale";
            $filename = 'prodotti_export_totale.xls';
        } elseif ($isExportTable) {
            // Esporta solo i prodotti visibili (filtrati per subcategory_id o search_query)
            $filters = [];
            $params = [];

            if (isset($_POST['subcategory_id']) && !empty($_POST['subcategory_id'])) {
                $filters[] = "p.subcategory_id = ?";
                $params[] = (int)$_POST['subcategory_id'];
            }

            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $filters[] = "(p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
                $like_query = '%' . $_POST['search'] . '%';
                $params[] = $like_query;
                $params[] = $like_query;
                $params[] = $like_query;
            }

            if (empty($filters)) {
                throw new Exception("Nessun parametro di filtro fornito per l'esportazione della tabella.");
            }

            $where_clause = implode(' AND ', $filters);

            $stmt = $db->prepare("
                SELECT p.name, p.description, p.model, p.manufacturer, s.name AS supplier_name,
                       p.quantity, p.availability, p.purchase_price, p.shelf, p.barcode
                FROM products p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE $where_clause
                ORDER BY p.name
            ");
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $export_type = "Export Tabella";
            $filename = 'prodotti_export_tabella.xls';
        } else {
            throw new Exception("Tipo di esportazione non riconosciuto.");
        }

        if (empty($products)) {
            throw new Exception("Nessun prodotto trovato per l'esportazione.");
        }

        // Imposta l'intestazione per il download del file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Inizia il buffer di output
        ob_start();

        // Inizio del file Excel
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Nome</th>';
        echo '<th>Descrizione</th>';
        echo '<th>Modello</th>';
        echo '<th>Produttore</th>';
        echo '<th>Fornitore</th>';
        echo '<th>Quantità</th>';
        echo '<th>Disponibilità</th>';
        echo '<th>Prezzo d\'acquisto</th>';
        echo '<th>Scaffale</th>';
        echo '<th>Barcode</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Scrivi i dati dei prodotti
        foreach ($products as $product) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($product['name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['description'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['model'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['manufacturer'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['supplier_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['quantity'] ?? 0) . '</td>';
            echo '<td>' . (isset($product['availability']) && $product['availability'] ? 'Disponibile' : 'Non Disponibile') . '</td>';
            echo '<td>' . number_format($product['purchase_price'] ?? 0, 2, ',', '.') . '</td>';
            echo '<td>' . htmlspecialchars($product['shelf'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['barcode'] ?? '') . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Invia l'output e termina
        ob_end_flush();
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = 'Errore durante l\'esportazione: ' . $e->getMessage();
        header('Location: ../pages/dashboard.php');
        exit();
    }
} else {
    $_SESSION['error'] = "Richiesta non valida.";
    header('Location: ../pages/dashboard.php');
    exit();
}
?>
