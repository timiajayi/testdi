<?php
function getGeneratedCards() {
    $cards = [];
    $files = glob('generated/*_front.jpg');
    
    foreach ($files as $file) {
        $filename = basename($file);
        $back_image = str_replace('_front.jpg', '_back.jpg', $filename);
        
        preg_match('/(\d{8}_\d{6})/', $filename, $matches);
        $date = isset($matches[1]) ? date('Y-m-d H:i:s', strtotime(str_replace('_', ' ', $matches[1]))) : '';
        
        $name = explode('_', $filename)[0];
        $name = ucwords(str_replace('_', ' ', $name));
        
        $cards[] = [
            'name' => $name,
            'date' => $date,
            'front_image' => 'generated/' . $filename,
            'back_image' => 'generated/' . $back_image
        ];
    }
    
    // Sort cards by date (newest first)
    usort($cards, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $cards;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>ID Card Gallery</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .search-container {
            margin: 20px 0;
            text-align: center;
        }
        
        #searchInput {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card img {
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .card-info {
            margin-bottom: 10px;
        }
        
        .download-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .nav-links {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
        }
        .delete-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 14px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #c82333;
        }
        
        .print-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 14px;
            cursor: pointer;
        }

        .print-btn:hover {
            background: #138496;
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="home.php">Generate New ID Card</a>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by name...">
    </div>

    <div class="gallery" id="cardGallery">
        <?php
        $cards = getGeneratedCards();
        foreach ($cards as $card) {
            echo "<div class='card' data-name='{$card['name']}'>";
            echo "<div class='card-info'>";
            echo "<h3>{$card['name']}</h3>";
            echo "<p>Generated: {$card['date']}</p>";
            echo "</div>";
            echo "<img src='{$card['front_image']}' alt='Front'>";
            echo "<img src='{$card['back_image']}' alt='Back'>";
            echo "<div class='card-actions'>";
            echo "<a href='{$card['front_image']}' download class='download-btn'>Download Front</a>";
            echo "<a href='{$card['back_image']}' download class='download-btn'>Download Back</a>";
            echo "<button class='print-btn' onclick='printCard(\"{$card['front_image']}\", \"{$card['back_image']}\")'>Print Card</button>";
            echo "<button class='delete-btn' onclick='deleteCard(event, \"{$card['front_image']}\")'>Delete</button>";
            echo "</div>";
            echo "</div>";
            
        }
        ?>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                if (name.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        async function deleteCard(event, filename) {
        if (confirm('Are you sure you want to delete this ID card?')) {
                try {
                    const formData = new FormData();
                    formData.append('filename', filename);
                    
                    const response = await fetch('delete_card.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        const card = event.target.closest('.card');
                        card.remove();
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        }

        // Add this function to your existing script section
        function printCard(frontImage, backImage) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print ID Card</title>
                    <style>
                        @media print {
                            img {
                                max-width: 100%;
                                page-break-after: always;
                            }
                        }
                        body {
                            margin: 0;
                            padding: 20px;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                    <img src="${frontImage}" alt="Front">
                    <img src="${backImage}" alt="Back">
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onfocus = function() { window.close(); }
                        }
                    <\/script>
                </body>
                </html>
            `);
        }

    </script>
</body>
</html>
