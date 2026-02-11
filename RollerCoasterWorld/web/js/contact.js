document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contact-form");
  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const reasonSelect = document.getElementById("reason");
  const subjectInput = document.getElementById("subject");
  const messageInput = document.getElementById("message");
  const submitBtn = document.getElementById("submit");
  const errorMsg = document.getElementById("error");
  const successMsg = document.getElementById("success");
  const charCount = document.getElementById("char-count");

  messageInput.addEventListener("input", () => {
    const count = messageInput.value.length;
    charCount.textContent = count;

    if (count >= 20) {
      charCount.style.color = "green";
    } else {
      charCount.style.color = "red";
    }
  });

  function showError(message) {
    errorMsg.textContent = message;
  }

  function hideError() {
    errorMsg.textContent = "";
  }

  function showSuccess(message) {
    successMsg.textContent = message;
  }

  function hideSuccess() {
    successMsg.textContent = "";
  }

  //Validación email
  emailInput.addEventListener("blur", () => {
    const emailError = document.getElementById("email-error");
    const emailRegex = /^[A-Za-z0-9._%+-]+@[a-z0-9]+\.[a-z]{2,4}$/;
    if (!emailRegex.test(emailInput.value)) {
      emailError.textContent =
        "Por favor, introduce un correo electrónico válido. Ejemplo: nombre@dominio.com";
      emailInput.style.borderColor = "red";
      return;
    }
    emailError.textContent = "";
    emailInput.style.borderColor = "green";
  });

  //Validación de texto
  messageInput.addEventListener("blur", () => {
    const messageError = document.getElementById("message-error");
    if (messageInput.value.length < 20) {
      messageError.textContent =
        "Por favor, introduce un mensaje con al menos 20 caracteres";
      messageInput.style.borderColor = "red";
      return;
    }
    messageError.textContent = "";
    messageInput.style.borderColor = "green";
  });

  submitBtn.addEventListener("click", async (e) => {
    e.preventDefault();
    const frecuencySelect = document.querySelector(
      "input[name=frecuency]:checked",
    );

    //Validación de campos vacíos
    if (
      nameInput.value.trim() === "" ||
      emailInput.value.trim() === "" ||
      reasonSelect.value === "" ||
      subjectInput.value.trim() === "" ||
      messageInput.value.trim() === ""
    ) {
      showError("Por favor, completa todos los campos");
      hideSuccess();
      return;
    }
    if (!frecuencySelect) {
      showError("Por favor, selecciona una frecuencia");
      hideSuccess();
      return;
    }
    hideError();

    //Después de las validaciones
    submitBtn.disabled = true;
    submitBtn.textContent = "Enviando...";

    try {
      const response = await fetch("../../../api/php/contact.php", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();

      if (result.success) {
        showSuccess("Mensaje enviado correctamente. Gracias por contactarnos");
        form.reset();
        emailInput.style.borderColor = "";
        messageInput.style.borderColor = "";
        emailError.textContent = "";
        messageError.textContent = "";
        hideError();

        setTimeout(() => {
          hideSuccess();
          window.location.href = "../index.php";
        }, 10000);
      } else {
        showError("Error al enviar el mensaje: " + result.error);
        hideSuccess();
      }
    } catch (error) {
      showError("Error de conexión: " + error);
      hideSuccess();
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Enviar mensaje";
    }
  });
});
