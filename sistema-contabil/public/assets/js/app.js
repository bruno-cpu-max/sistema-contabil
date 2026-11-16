/*
 * SisContábil — comportamentos da interface (JavaScript puro, sem bibliotecas).
 * Tudo aqui é conforto: sem JavaScript o sistema continua funcionando, e todas as
 * validações valem de verdade no servidor.
 */
(function () {
  'use strict';

  // Menu lateral no celular.
  const menu = document.getElementById('menu-lateral');
  const fundo = document.querySelector('[data-fechar-menu]');
  const botaoMenu = document.querySelector('[data-abrir-menu]');

  function alternarMenu(abrir) {
    if (!menu || !fundo || !botaoMenu) {
      return;
    }
    menu.classList.toggle('lateral--aberta', abrir);
    fundo.hidden = !abrir;
    botaoMenu.setAttribute('aria-expanded', String(abrir));
  }

  if (botaoMenu) {
    botaoMenu.addEventListener('click', () => alternarMenu(true));
  }
  if (fundo) {
    fundo.addEventListener('click', () => alternarMenu(false));
  }
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
      alternarMenu(false);
    }
  });

  // Confirmação antes de excluir.
  document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (evento) => {
      if (!window.confirm(form.getAttribute('data-confirmar'))) {
        evento.preventDefault();
      }
    });
  });

  // Fechar avisos.
  document.querySelectorAll('[data-fechar-alerta]').forEach((botao) => {
    botao.addEventListener('click', () => botao.closest('.alerta').remove());
  });

  // Barras de progresso do painel (a largura vem do servidor em data-largura).
  document.querySelectorAll('[data-largura]').forEach((parte) => {
    const largura = Math.max(0, Math.min(100, Number(parte.getAttribute('data-largura')) || 0));
    requestAnimationFrame(() => {
      parte.style.width = largura + '%';
    });
  });

  // Máscaras de digitação (CPF/CNPJ e telefone). O servidor aceita com ou sem pontuação.
  const mascaras = {
    documento(numeros) {
      numeros = numeros.slice(0, 14);
      if (numeros.length <= 11) {
        return numeros
          .replace(/^(\d{3})(\d)/, '$1.$2')
          .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
          .replace(/\.(\d{3})(\d{1,2})$/, '.$1-$2');
      }
      return numeros
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    },
    telefone(numeros) {
      numeros = numeros.slice(0, 11);
      if (numeros.length <= 2) {
        return numeros.length ? '(' + numeros : '';
      }
      const meio = numeros.length === 11 ? 5 : 4;
      const ddd = '(' + numeros.slice(0, 2) + ') ';
      const resto = numeros.slice(2);
      return resto.length > meio ? ddd + resto.slice(0, meio) + '-' + resto.slice(meio) : ddd + resto;
    },
  };

  document.querySelectorAll('[data-mascara]').forEach((campo) => {
    const mascara = mascaras[campo.getAttribute('data-mascara')];
    if (!mascara) {
      return;
    }
    campo.addEventListener('input', () => {
      campo.value = mascara(campo.value.replace(/\D/g, ''));
    });
  });

  // Envio de arquivo: mostra o nome escolhido e aceita arrastar e soltar.
  document.querySelectorAll('.envio').forEach((area) => {
    const input = area.querySelector('input[type="file"]');
    const texto = area.querySelector('[data-nome-arquivo]');
    if (!input || !texto) {
      return;
    }
    input.addEventListener('change', () => {
      texto.textContent = input.files.length ? 'Selecionado: ' + input.files[0].name : 'Clique para escolher o arquivo';
    });
    ['dragenter', 'dragover'].forEach((tipo) => area.addEventListener(tipo, () => area.classList.add('envio--arrastando')));
    ['dragleave', 'drop'].forEach((tipo) => area.addEventListener(tipo, () => area.classList.remove('envio--arrastando')));
  });
})();
