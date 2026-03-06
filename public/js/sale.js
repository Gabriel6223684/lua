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
  try {
    // Se já existe ID, não precisa criar novamente
    if (Id.value && Id.value !== '') {
      // Atualiza os totais da venda
      const response = await Requests.SetForm("form").Post("/venda/update");
      if (response.status) {
        await listItemSale();
      }
      return;
    }
    
    // Criar nova venda (sem validação pois não precisa de produto para criar venda)
    const formData = new FormData();
    formData.append('acao', 'c');
    
    const response = await fetch('/venda/insert', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (!data.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: data.msg || "Ocorreu um erro ao inserir a venda.",
        time: 3000,
        progressBar: true,
      });
      return;
    }
    Action.value = "e";
    Id.value = data.id;
    window.history.pushState({}, "", `/venda/alterar/${data.id}`);
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
  const pesquisaSelect = document.getElementById('pesquisa');
  const produtoId = pesquisaSelect ? pesquisaSelect.value : null;
  
  if (!produtoId || produtoId === '') {
    Swal.fire({
      icon: "warning",
      title: "Atenção",
      text: "Selecione um produto primeiro!",
      time: 2000,
      progressBar: true,
    });
    return;
  }
  
  // Garantir que existe uma venda criada
  if (!Id.value || Id.value === '') {
    await InsertSale();
  }
  
  try {
    const response = await Requests.SetForm("form").Post("/venda/insertitem");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Ocorreu um erro ao inserir o item.",
        time: 3000,
        progressBar: true,
      });
      return;
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao inserir o item.",
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
      let quantidade = item?.quantidade || 1;
      trs += `
                <tr>
                    <td>${item.id}</td>
                    <td>${item.nome}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${quantidade - 1})">-</button>
                            <span class="badge bg-primary">${quantidade}</span>
                            <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${quantidade + 1})">+</button>
                        </div>
                    </td>
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
  try {
    const formData = new FormData();
    formData.append('id', idItem);
    
    const response = await fetch('/venda/deleteitem', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (!data.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: data.msg || "Não foi possível excluir o item",
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
    console.error('Erro ao excluir item:', error);
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao excluir o item.",
      time: 2000,
      progressBar: true,
    });
  }
}

// Tornar função global para uso no onclick
window.DeleteItem = DeleteItem;

// Função para atualizar a quantidade de um item
async function updateQuantity(idItem, novaQuantidade) {
  if (novaQuantidade < 1) {
    // Se a quantidade for menor que 1, excluir o item
    await DeleteItem(idItem);
    return;
  }
  
  const form = document.getElementById('form');
  const inputId = document.createElement('input');
  inputId.type = 'hidden';
  inputId.name = 'id';
  inputId.value = idItem;
  
  const inputQuantidade = document.createElement('input');
  inputQuantidade.type = 'hidden';
  inputQuantidade.name = 'quantidade';
  inputQuantidade.value = novaQuantidade;
  
  form.appendChild(inputId);
  form.appendChild(inputQuantidade);

  try {
    const response = await Requests.SetForm("form").Post("/venda/updateitem");
    if (!response.status) {
      Swal.fire({
        icon: "error",
        title: "Erro",
        text: response.msg || "Não foi possível atualizar a quantidade",
        time: 2000,
        progressBar: true,
      });
      return;
    }
    // Recarrega a lista de itens
    await listItemSale();
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro",
      text: error.message || "Ocorreu um erro ao atualizar a quantidade.",
      time: 2000,
      progressBar: true,
    });
  }
  // Remove os inputs temporários
  form.removeChild(inputId);
  form.removeChild(inputQuantidade);
}

// Tornar função global para uso no onclick
window.updateQuantity = updateQuantity;

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

// ==========================================
// FUNÇÕES DO MODAL DE PESQUISA DE PRODUTO (F4)
// ==========================================

let allProducts = [];

// Função para carregar todos os produtos
async function loadProducts() {
  const tbody = document.getElementById('tableProductsBody');
  tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Carregando produtos...</td></tr>';
  
  try {
    const formData = new FormData();
    formData.append('search', '');
    
    const response = await fetch('/produto/listproductall', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.status) {
      allProducts = data.data || [];
      renderProducts(allProducts);
    } else {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar produtos</td></tr>';
    }
  } catch (error) {
    console.error('Erro ao carregar produtos:', error);
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar produtos</td></tr>';
  }
}

// Função para renderizar a tabela de produtos
function renderProducts(products) {
  const tbody = document.getElementById('tableProductsBody');
  
  if (!products || products.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Nenhum produto encontrado</td></tr>';
    return;
  }
  
  let html = '';
  products.forEach(product => {
    const valor = parseFloat(product.valor || 0).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
    const estoque = product.estoque || 0;
    const estoqueClass = estoque > 0 ? 'text-success' : 'text-danger';
    
    html += `
      <tr>
        <td>${product.id}</td>
        <td>${product.codigo_barra || '-'}</td>
        <td><strong>${product.nome}</strong></td>
        <td>${product.descricao_curta || '-'}</td>
        <td class="${estoqueClass}">${estoque}</td>
        <td><strong>${valor}</strong></td>
        <td>
          <button class="btn btn-success btn-sm" onclick="selectProduct(${product.id})">
            <i class="fas fa-plus"></i> Adicionar
          </button>
        </td>
      </tr>
    `;
  });
  
  tbody.innerHTML = html;
}

// Função para filtrar produtos
function filterProducts(searchTerm) {
  const term = searchTerm.toLowerCase().trim();
  
  if (!term) {
    renderProducts(allProducts);
    return;
  }
  
  const filtered = allProducts.filter(product => {
    return (
      String(product.id).includes(term) ||
      (product.nome && product.nome.toLowerCase().includes(term)) ||
      (product.codigo_barra && product.codigo_barra.toLowerCase().includes(term)) ||
      (product.descricao_curta && product.descricao_curta.toLowerCase().includes(term))
    );
  });
  
  renderProducts(filtered);
}

// Função para limpar a busca
function clearSearchProduct() {
  const searchInput = document.getElementById('searchProduct');
  searchInput.value = '';
  renderProducts(allProducts);
  searchInput.focus();
}

// Função global para limpar busca
window.clearSearchProduct = clearSearchProduct;

// Função para verificar se o produto já está no carrinho
function isProductInCart(productId) {
  const tbody = document.getElementById('products-table-tbody');
  if (!tbody) return false;
  
  const rows = tbody.querySelectorAll('tr');
  for (let row of rows) {
    const cells = row.querySelectorAll('td');
    if (cells.length > 0) {
      const idCell = cells[0].textContent.trim();
      if (idCell == productId) {
        return true;
      }
    }
  }
  return false;
}

// Função para selecionar um produto do modal
async function selectProduct(productId) {
  // Verificar se o produto já está no carrinho
  if (isProductInCart(productId)) {
    Swal.fire({
      icon: 'warning',
      title: 'Produto já adicionado',
      text: 'Este produto já está na venda. Você pode alterar a quantidade na lista de itens.',
      timer: 3000,
      progressBar: true
    });
    return;
  }
  
  // Verificar se existe uma venda criada
  if (!Id.value || Id.value === '') {
    // Criar a venda primeiro
    await InsertSale();
  }
  
  // Definir o produto no campo de pesquisa
  const pesquisaSelect = document.getElementById('pesquisa');
  if (pesquisaSelect) {
    // Criar uma opção para o produto selecionado
    const option = document.createElement('option');
    option.value = productId;
    option.textContent = `Produto ${productId}`;
    pesquisaSelect.appendChild(option);
    pesquisaSelect.value = productId;
  }
  
  // Inserir o item na venda
  await InsertItemSale();
  
  // Atualizar a lista de itens
  await listItemSale();
  
  // Fechar o modal
  const myModalEl = document.getElementById('pesquisaProdutoModal');
  if (myModalEl) {
    const modal = bootstrap.Modal.getInstance(myModalEl);
    if (modal) {
      modal.hide();
    }
  }
  
  Swal.fire({
    icon: 'success',
    title: 'Sucesso',
    text: 'Produto adicionado à venda!',
    timer: 2000,
    progressBar: true
  });
}

// Função global para selecionar produto
window.selectProduct = selectProduct;

// Configurar o evento de busca no input
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchProduct');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      filterProducts(e.target.value);
    });
  }
  
  // Carregar produtos quando o modal for aberto
  const pesquisaModal = document.getElementById('pesquisaProdutoModal');
  if (pesquisaModal) {
    pesquisaModal.addEventListener('shown.bs.modal', () => {
      loadProducts();
    });
  }
});

