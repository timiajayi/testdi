<?php
function getGeneratedCards() {
    $cards = [];
    $files = glob('generated/*_front.jpg');
    
    foreach ($files as $file) {
        $filename = basename($file);
        $back_image = str_replace('_front.jpg', '_back.jpg', $filename);
        
        // Extract date
        preg_match('/(\d{8}_\d{6})/', $filename, $matches);
        $date = isset($matches[1]) ? date('Y-m-d H:i:s', strtotime(str_replace('_', ' ', $matches[1]))) : '';
        
        // Extract name and additional info from filename
        $parts = explode('_', $filename);
        $name = ucwords(str_replace('_', ' ', $parts[0]));
        
        // Extract or set default values for ID and department
        $id_number = isset($parts[1]) ? $parts[1] : '';
        $department = isset($parts[2]) ? ucwords(str_replace('_', ' ', $parts[2])) : '';
        
        $cards[] = [
            'name' => $name,
            'date' => $date,
            'id_number' => $id_number,
            'department' => $department,
            'front_image' => 'generated/' . $filename,
            'back_image' => 'generated/' . $back_image
        ];
    }
    
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

        .filter-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
        margin: 20px 0;
        }
        
        .filter-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="home.php">Generate New ID Card</a>
    </div>

    <div class="search-container">
    <div class="filter-group">
    <input type="text" id="nameSearch" placeholder="Search by First Name..." class="filter-input">
    <input type="text" id="idSearch" placeholder="Search by Last Name..." class="filter-input">
    <input type="date" id="dateSearch" class="filter-input">
</div>


    <div class="gallery" id="cardGallery">
        <?php
        $cards = getGeneratedCards();
        foreach ($cards as $card) {
            echo "<div class='card' 
                      data-name='{$card['name']}'
                      data-id='{$card['id_number']}'
                      data-date='{$card['date']}'>";
            echo "<div class='card-info'>";
            echo "<h3>First Name: {$card['name']}</h3>";
            echo "<p>Last Name: {$card['id_number']}</p>";
            echo "<p>Generated: " . date('Y-m-d', strtotime($card['date'])) . "</p>";
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
        function filterCards() {
            const nameFilter = document.getElementById('nameSearch').value.toLowerCase();
            const lastNameFilter = document.getElementById('idSearch').value.toLowerCase();
            const dateFilter = document.getElementById('dateSearch').value;
            
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                const cardData = {
                    name: card.getAttribute('data-name')?.toLowerCase() || '',
                    lastName: card.getAttribute('data-id')?.toLowerCase() || '',
                    date: card.getAttribute('data-date') ? new Date(card.getAttribute('data-date')).toISOString().split('T')[0] : ''
                };

                const matchesName = cardData.name.includes(nameFilter);
                const matchesLastName = cardData.lastName.includes(lastNameFilter);
                const matchesDate = !dateFilter || cardData.date === dateFilter;

                card.style.display = 
                    matchesName && matchesLastName && matchesDate ? 'block' : 'none';
            });
        }

        // Event listeners
        document.getElementById('nameSearch').addEventListener('input', filterCards);
        document.getElementById('idSearch').addEventListener('input', filterCards);
        document.getElementById('dateSearch').addEventListener('input', filterCards);

            
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


        function printCard(frontImage, backImage) {
         const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print ID Card</title>
                    <style>
                        @page {
                            size: letter;  /* Using standard letter size */
                        }
                        .card-container {
                            width: 100%;
                            max-width: 8.5in;
                            margin: 0 auto;
                            padding: 0.5in;
                        }
                        .card-image {
                            display: block;
                            width: 2.204in;
                            margin: 0 auto 1in auto;
                        }
                    </style>
                </head>
                <body>
                    <div class="card-container">
                        <img src="${frontImage}" alt="Front" class="card-image">
                        <img src="${backImage}" alt="Back" class="card-image">
                    </div>
                </body>
                </html>
            `);
            
            // Wait for images to load before printing
            printWindow.document.close();
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 250);
            };
        }


    </script>
</body>
</html>
