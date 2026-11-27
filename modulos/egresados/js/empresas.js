/**
 * MÓDULO EMP · Validación en tiempo real para RFC y Teléfono
 */
document.addEventListener('DOMContentLoaded', () => {
  const inputRfc = document.getElementById('rfc');
  const inputTel = document.getElementById('telefono');

  if (inputRfc) {
    inputRfc.addEventListener('input', () => {
      const crudo = inputRfc.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g, '');
      let limpio = '';

      for (let i = 0; i < crudo.length && limpio.length < 12; i++) {
        const ch = crudo[i];
        const pos = limpio.length;

        if (pos < 3) {
          // Posiciones 0, 1, 2: solo letras
          if (/^[A-ZÑ&]$/.test(ch)) limpio += ch;
        } else if (pos < 9) {
          // Posiciones 3 a 8 (6 dígitos): solo números
          if (/^[0-9]$/.test(ch)) limpio += ch;
        } else {
          // Posiciones 9, 10, 11 (3 de homoclave): letras o números
          if (/^[A-Z0-9]$/.test(ch)) limpio += ch;
        }
      }

      inputRfc.value = limpio;
    });
  }

  if (inputTel) {
    inputTel.addEventListener('input', () => {
      // Solo dígitos numéricos, máximo 10
      inputTel.value = inputTel.value.replace(/\D/g, '').slice(0, 10);
    });
  }
});