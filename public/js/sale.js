import { Validate } from "./Validate.js";
import { Requests } from "./Requests.js";

const Action = document.getElementById("acao");
const Id = document.getElementById("id");
const insertItemButton = document.getElementById("insertItemButton");

// Atualizar relógio em tempo real
function updateClock() {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, "0");
  const minutes = String(now.getMinutes()).padStart(2, "0");
  const seconds = String(now.getSeconds()).padStart(2, "0");

  const days = [
    "Domingo",
    "Segunda-Feira",
    "Terça-Feira",
    "Quarta-Feira",
    "Quinta-Feira",
    "Sexta-Feira",
    "Sábado",
  ];

  const months = [
    "Janeiro",
    "Fevereiro",
    "Março",
    "Abril",
    "Maio",
    "Junho",
    "Julho",
    "Agosto",
    "Setembro",
    "Outubro",
    "Novembro",
    "Dezembro",
  ];

  const dayName = days[now.getDay()];
  const day = now.getDate();
  const month = months[now.getMonth()];
  const year = now.getFullYear();
  const timeElement = document.querySelector(".time");
  const dateElement = document.querySelector(".date");
  if (timeElement) {
    timeElement.textContent = `${hours}:${minutes}:${seconds}`;
  }
  if (dateElement) {
    dateElement.textContent = `${dayName}, ${day} De ${month} De ${year}`;
  }
}

// Atualizar a cada segundo
setInterval(updateClock, 1000);

//Insere uma nova venda
async function InsertSale() {
  const valid = Validate.SetForm("form").Validate();
  if (!valid) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: "Por favor, preencha os campos corretamente.",
      time: 2000,
      progressBar: true,
    });
    return;
  }
  try {
    const response =
      Action.value === "c"
        ? await Requests.SetForm("form").Post("/venda/insert")
        : await Requests.SetForm("form").Post("/venda/update");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Ocorreu um erro ao inserir a venda.",
        time: 3000,
        progressBar: true,
      });
      return;
    }
    Action.value = "e";
    Id.value = response.id;
    window.history.pushState({}, "", `/venda/alterar/${response.id}`);
    await listItemSale();
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao inserir a venda.",
      time: 3000,
      progressBar: true,
    });
  }
}

async function InsertItemSale() {
  const valid = Validate.SetForm("form").Validate();
  if (!valid) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: "Por favor, preencha os campos corretamente.",
      time: 2000,
      progressBar: true,
    });
    return;
  }
  try {
    const response = await Requests.SetForm("form").Post("/venda/insertitem");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Ocorreu um erro ao inserir a venda.",
        time: 3000,
        progressBar: true,
      });
      return;
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao inserir a venda.",
      time: 3000,
      progressBar: true,
    });
  }
}

//Função para listar itens da venda
async function listItemSale() {
  try {
    const response = await Requests.SetForm("form").Post("/venda/listitemsale");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Não foi possivel listar os dados da venda",
        time: 2000,
        progressBar: true,
      });
      return;
    }

    let total_liquido = parseFloat(response?.sale?.total_liquido || 0);
    let total_bruto = parseFloat(response?.sale?.total_bruto || 0);

    document.getElementById("total-amount").innerText =
      total_liquido.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

    document.getElementById("amount").innerText = total_bruto.toLocaleString(
      "pt-BR",
      {
        style: "currency",
        currency: "BRL",
      },
    );

    let trs = "";
    response.data.forEach((item) => {
      let valorItem = parseFloat(item?.total_liquido || 0).toLocaleString(
        "pt-BR",
        {
          style: "currency",
          currency: "BRL",
        },
      );
      trs += `
                <tr>
                    <td>${item.id}</td>
                    <td>${item.nome}</td>
                    <td>${valorItem}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="DeleteItem(${item.id})">
                            <i class="bi bi-trash-fill"></i> Excluir
                        </button>
                    </td>
                </tr>
           `;
    });

    //Atualizamos o itens da venda na tabela
    document.getElementById("products-table-tbody").innerHTML = trs;
    //Atualizamos o total de itens vencido.
    document.getElementById("product-count").innerText =
      `Itens ${response.data.length}`;
  } catch (error) {
    console.error("Erro ao listar itens:", error);
  }
}

//Função para excluir item da venda
async function DeleteItem(idItem) {
  const form = document.getElementById('form');
  const inputId = document.createElement('input');
  inputId.type = 'hidden';
  inputId.name = 'id';
  inputId.value = idItem;
  form.appendChild(inputId);

  try {
    const response = await Requests.SetForm("form").Post("/venda/deleteitem");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Não foi possível excluir o item",
        time: 2000,
        progressBar: true,
      });
      return;
    }
    Swal.fire({
      icon: "success",
      title: "Sucesso",
      text: "Item excluído com sucesso!",
      time: 2000,
      progressBar: true,
    });
    // Recarrega a lista de itens
    await listItemSale();
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao excluir o item.",
      time: 2000,
      progressBar: true,
    });
  }
  // Remove o input temporário
  form.removeChild(inputId);
}

// Tornar função global para uso no onclick
window.DeleteItem = DeleteItem;

// Event Listeners para botões de adicionar
document.addEventListener("DOMContentLoaded", async () => {
  if (Action.value === "e") {
    await listItemSale();
  }
});

// Feedback visual para cliques
document.addEventListener("click", function (e) {
  if (e.target.matches("button")) {
    e.target.style.transition = "transform 0.1s";
  }
});

insertItemButton.addEventListener("click", async () => {
  //Salva os dados da venda
  await InsertSale();
  //Salva o item da venda
  await InsertItemSale();
  
  await listItemSale();
});

// Atalhos de teclado
document.addEventListener("keydown", (e) => {
  //Abrimos o modal de pesquisa de produto com a tecla F4
  if (e.key === "F4") {
    const myModalEl = document.getElementById("pesquisaProdutoModal");
    if (myModalEl) {
      const modal = new bootstrap.Modal(myModalEl);
      modal.show();
    }
  }
  //Fechamos o modal de pesquisa de produto com a tecla F8
  if (e.key === "F8") {
    const myModalEl = document.getElementById("pesquisaProdutoModal");
    if (myModalEl) {
      const modal = bootstrap.Modal.getInstance(myModalEl);
      if (modal) {
        modal.hide();
      }
    }
  }
  //F5 - Atualizar lista de itens
  if (e.key === "F5") {
    e.preventDefault();
    listItemSale();
  }
});

$("#diaVencimento").flatpickr({
  locale: "pt",
  dateFormat: "d/m/Y",
});

$("#pesquisa").select2({
  theme: "bootstrap-5",
  placeholder: "Selecione um produto",
  language: "pt-BR",
  ajax: {
    url: "/produto/listproductdata",
    type: "POST",
  },
});

$(".form-select").on("select2:open", function (e) {
  let inputElement = document.querySelector(".select2-search__field");
  inputElement.placeholder = "Digite para pesquisar...";
  inputElement.focus();
});

