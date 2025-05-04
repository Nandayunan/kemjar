document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("registrationForm");
    const notification = document.getElementById("notification");
  
    form.addEventListener("submit", function(event) {
      event.preventDefault();
  
      const data = {
        nama: document.getElementById("nama").value,
        ttl: document.getElementById("ttl").value,
        nomor: document.getElementById("nomor").value,
        alamat: document.getElementById("alamat").value,
        email: document.getElementById("email").value,
        password: document.getElementById("password").value,
      };
  
      // Simulate an API call
      fakeApiCall(data)
        .then(response => {
          showNotification("Pendaftaran Berhasil", "alert-success");
        })
        .catch(error => {
          showNotification("Pendaftaran Gagal.", "alert-danger");
        });
    });
  
    function fakeApiCall(data) {
      return new Promise((resolve, reject) => {
        setTimeout(() => {
          // Simulate success or failure
          if (Math.random() > 0.5) {
            resolve("Success");
          } else {
            reject("Error");
          }
        }, 1000);
      });
    }
  
    function showNotification(message, alertType) {
      notification.textContent = message;
      notification.className = `alert ${alertType}`;
      notification.style.display = "block";
      setTimeout(() => {
        notification.style.display = "none";
      }, 3000);
    }
  });
  