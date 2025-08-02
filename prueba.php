<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Tabla Dinámica con JavaScript</title>
  <style>
    table {
      width: 60%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #999;
      padding: 8px;
      text-align: center;
    }
    th {
      background-color: #eee;
    }
    input[type="text"], input[type="number"] {
      padding: 5px;
      margin: 5px;
    }
  </style>
</head>
<body>

  <h2>Tabla Dinámica con JavaScript</h2>

  <form id="dataForm">
    <input type="text" id="name" placeholder="Nombre" required />
    <input type="number" id="age" placeholder="Edad" required min="0" />
    <input type="text" id="city" placeholder="Ciudad" required />
    <button type="submit">Agregar</button>
  </form>

  <table id="dataTable">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Edad</th>
        <th>Ciudad</th>
      </tr>
    </thead>
    <tbody>
      <!-- Filas dinámicas aquí -->
    </tbody>
  </table>

  <script>
    // Datos iniciales inventados
    const data = [
      { name: "Ana", age: 25, city: "Buenos Aires" },
      { name: "Carlos", age: 30, city: "Córdoba" },
      { name: "Lucía", age: 22, city: "Rosario" },
    ];

    const tableBody = document.querySelector("#dataTable tbody");
    const form = document.getElementById("dataForm");

    // Función para renderizar la tabla con los datos
    function renderTable() {
      tableBody.innerHTML = ""; // Limpia tabla antes de renderizar
      data.forEach(item => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${item.name}</td>
          <td>${item.age}</td>
          <td>${item.city}</td>
        `;
        tableBody.appendChild(row);
      });
    }

    // Evento submit del formulario para agregar datos nuevos
    form.addEventListener("submit", e => {
      e.preventDefault();

      const name = document.getElementById("name").value.trim();
      const age = parseInt(document.getElementById("age").value);
      const city = document.getElementById("city").value.trim();

      if(name && age >= 0 && city) {
        data.push({ name, age, city });
        renderTable();
        form.reset(); // Limpia formulario
      }
    });

    // Renderizamos la tabla inicialmente
    renderTable();
  </script>

</body>
</html>
