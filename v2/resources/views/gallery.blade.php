<!DOCTYPE html>
<html>

<head>
    <title>ID Card Gallery</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/gallery.css') }}">
</head>

<body>
    <div class="nav-links">
        <a href="{{ route('home') }}">Generate New ID Card</a>
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="nav-link" style="border: none; cursor: pointer;">Logout</button>
        </form>
    </div>

    <div class="filter-group">
        <input type="text" id="nameSearch" placeholder="Search by First Name..." class="filter-input">
        <input type="text" id="idSearch" placeholder="Search by Last Name..." class="filter-input">
        <input type="date" id="dateSearch" class="filter-input">
        <select id="regionSearch" class="filter-input">
            <option value="">All Regions</option>
            <option value="HQ">HQ</option>
            <option value="IKEJA">IKEJA</option>
            <option value="ILLORIN">ILLORIN</option>
            <option value="IBADAN">IBADAN</option>
            <option value="ABA">ABA</option>
            <option value="ABUJA">ABUJA</option>
            <option value="KADUNA">KADUNA</option>
            <option value="KANO">KANO</option>
            <option value="BENIN">BENIN</option>
        </select>
    </div>

    <div class="gallery" id="cardGallery">
        @foreach($cards as $card)
        <div class="card"
            data-name="{{ $card['name'] }}"
            data-lastname="{{ $card['last_name'] }}"
            data-date="{{ date('Y-m-d', strtotime($card['date'])) }}"
            data-region="{{ $card['region'] }}">
            <div class="card-info">
                <h3>First Name: {{ $card['name'] }}</h3>
                <p>Last Name: {{ $card['last_name'] }}</p>
                <p>Region: {{ $card['region'] ?? 'N/A' }}</p>
                <p>Generated: {{ date('Y-m-d', strtotime($card['date'])) }}</p>
            </div>
            <img src="{{ asset($card['front_image']) }}" alt="Front">
            <img src="{{ asset($card['back_image']) }}" alt="Back">
            <div class="card-actions">
                <a href="{{ asset($card['front_image']) }}" download class="download-btn" onclick="showMessage('Download started')">Download Front</a>
                <a href="{{ asset($card['back_image']) }}" download class="download-btn" onclick="showMessage('Download started')">Download Back</a>
                <button class="print-btn" onclick='printCard("{{ asset($card['front_image']) }}", "{{ asset($card['back_image']) }}")'>Print Card</button>
                <button class="delete-btn" onclick="showDeleteModal('{{ basename($card['front_image']) }}', this)">Delete</button>
            </div>
        </div>
        @endforeach
    </div>

    <div class="pagination-container">
        {{ $cards->links() }}
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this ID card?</p>
            <div class="modal-buttons">
                <button id="confirmDelete" class="btn-danger">Delete</button>
                <button id="cancelDelete" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>


    <script>
        function filterCards() {
            const nameFilter = document.getElementById('nameSearch').value.toLowerCase();
            const lastNameFilter = document.getElementById('idSearch').value.toLowerCase();
            const dateFilter = document.getElementById('dateSearch').value;
            const regionFilter = document.getElementById('regionSearch').value;

            const cards = document.querySelectorAll('.card');

            cards.forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                const lastName = card.getAttribute('data-lastname').toLowerCase();
                const date = card.getAttribute('data-date');
                const region = card.getAttribute('data-region');

                const matchesName = name.includes(nameFilter);
                const matchesLastName = lastName.includes(lastNameFilter);
                const matchesDate = !dateFilter || date === dateFilter;
                const matchesRegion = !regionFilter || region === regionFilter;

                card.style.display =
                    matchesName && matchesLastName && matchesDate && matchesRegion ? 'block' : 'none';
            });
        }

        document.getElementById('nameSearch').addEventListener('input', filterCards);
        document.getElementById('idSearch').addEventListener('input', filterCards);
        document.getElementById('dateSearch').addEventListener('input', filterCards);
        document.getElementById('regionSearch').addEventListener('change', filterCards);

    </script>
    <script>
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
    <script>
        function confirmDelete(filename, button) {
            if (confirm('Are you sure you want to delete this ID card?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("cards.destroy", "") }}/' + filename;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);

                fetch(form.action, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            button.closest('.card').remove();
                            showMessage('ID Card deleted successfully');
                        }
                    });
            }
        }

        function showMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Add this CSS
        const style = document.createElement('style');
        style.textContent = `
            .toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #333;
                color: white;
                padding: 12px 24px;
                border-radius: 4px;
                animation: fadeIn 0.3s, fadeOut 0.3s 2.7s;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);
    </script>

    <script>
        let currentCard = null;
        let currentButton = null;

        function showDeleteModal(filename, button) {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'block';
            currentCard = filename;
            currentButton = button;
        }

        document.getElementById('cancelDelete').onclick = function() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        document.getElementById('confirmDelete').onclick = function() {
            deleteCard(currentCard, currentButton);
            document.getElementById('deleteModal').style.display = 'none';
        }

        function deleteCard(filename, button) {
            const card = button.closest('.card');
            card.style.opacity = '0.5'; // Immediate visual feedback

            fetch(`{{ route('cards.destroy', '') }}/${filename}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        card.remove();
                        showToast('ID Card deleted successfully');
                    } else {
                        card.style.opacity = '1';
                    }
                });
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>

</html>