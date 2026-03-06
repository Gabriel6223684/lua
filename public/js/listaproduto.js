import { Requests } from "./Requests.js";
const tabela = new $('#tabela').DataTable({
    paging: true,
    lengthChange: true,
    searching: true,
    ordering: true,
    info: true,
    autoWidth: false,
    responsive: true,
    stateSave: true,
    select: true,
    processing: true,
    serverSide: true,
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json',
        searchPlaceholder: 'Digite sua pesquisa...'
    },
    ajax: {
        url: '/produto/listproduto',
        type: 'POST'
    },
    columnDefs: [
        {
            targets: [5],
            render: function (data, type, row) {
                if (type === 'display') {
                    return parseFloat(data).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                }
                return data;
            }
        },
        {
            targets: [4],
            render: function (data, type, row) {
                if (type === 'display') {
                    const estoque = parseInt(data);
                    let badgeClass = 'bg-secondary';
                    if (estoque > 10) badgeClass = 'bg-success';
                    else if (estoque > 0) badgeClass = 'bg-warning text-dark';
                    else badgeClass = 'bg-danger';
                    
                    return `<span class="badge ${badgeClass}">${estoque}</span>`;
                }
                return data;
            }
        }
    ]
});

// --- LÓGICA DE ATALHOS ---
document.addEventListener('keydown', function (e) {
    
    // F2 - Ir para Cadastro
    if (e.key === 'F2') {
        e.preventDefault();
        window.location.href = '/produto/cadastro';
    }
});

async function Delete(id) {
    document.getElementById('id').value = id;
    const response = await Requests.SetForm('form').Post('/produto/delete');
    if (!response.status) {
        Swal.fire({
            title: "Erro ao remover!",
            icon: "error",
            html: response.msg,
            timer: 3000,
            timerProgressBar: true
        });
        return;
    }
    Swal.fire({
        title: "Removido com sucesso!",
        icon: "success",
        html: response.msg,
        timer: 3000,
        timerProgressBar: true
    });
    tabela.ajax.reload();
}

function AdjustStock(id, currentStock) {
    document.getElementById('id').value = id;
    document.getElementById('estoqueAtual').value = currentStock;
    document.getElementById('estoqueAtualLabel').textContent = `Estoque Atual: ${currentStock}`;
    
    const modal = new bootstrap.Modal(document.getElementById('modalAdjustStock'));
    modal.show();
}

async function SaveStockAdjust() {
    const id = document.getElementById('id').value;
    const newStock = document.getElementById('novaQuantidade').value;
    
    if (!newStock || newStock < 0) {
        Swal.fire({
            title: "Erro!",
            icon: "error",
            html: "Por favor, informe uma quantidade válida.",
            timer: 3000,
            timerProgressBar: true
        });
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('quantidade', newStock);
    
    try {
        const response = await fetch('/produto/adjuststock', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.status) {
            Swal.fire({
                title: "Erro!",
                icon: "error",
                html: result.msg,
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }
        
        Swal.fire({
            title: "Sucesso!",
            icon: "success",
            html: result.msg,
            timer: 3000,
            timerProgressBar: true
        });
        
        bootstrap.Modal.getInstance(document.getElementById('modalAdjustStock')).hide();
        tabela.ajax.reload();
        
    } catch (error) {
        Swal.fire({
            title: "Erro!",
            icon: "error",
            html: "Erro ao ajustar estoque: " + error.message,
            timer: 3000,
            timerProgressBar: true
        });
    }
}

window.Delete = Delete;
window.AdjustStock = AdjustStock;
window.SaveStockAdjust = SaveStockAdjust;
