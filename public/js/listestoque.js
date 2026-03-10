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
        url: '/ajusteestoque/listajusteestoque',
        type: 'POST'
    },
    columnDefs: [
        {
            targets: [4],
            render: function (data, type, row) {
                if (type === 'display') {
                    return parseFloat(data).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                }
                return data;
            }
        }
    ]
    
});


async function Delete(id) {
    document.getElementById('id').value = id;
    const response = await Requests.SetForm('form').Post('/ajusteestoque/delete');
    if (!response.status) {
        Swal.fire({
            title: "Erro ao remover!",
            icon: "error",
            html: response.msg,
            timer: 3000,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        return;
    }
    Swal.fire({
        title: "Removido com sucesso!",
        icon: "success",
        html: response.msg,
        timer: 3000,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    tabela.ajax.reload();
}
window.Delete = Delete;

// Função para ajustar o estoque de um produto
async function ajustarEstoque(idProduto) {
    const inputQuantidade = document.getElementById('quantidade_ajuste_' + idProduto);
    if (!inputQuantidade || !inputQuantidade.value) {
        Swal.fire({
            title: "Erro!",
            icon: "error",
            html: "Informe a nova quantidade!",
            timer: 3000,
            timerProgressBar: true
        });
        return;
    }

    const formData = new FormData();
    formData.append('id', idProduto);
    formData.append('quantidade', inputQuantidade.value);

    try {
        const response = await fetch('/ajusteestoque/ajustar', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            Swal.fire({
                title: "Sucesso!",
                icon: "success",
                html: data.msg,
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // Fechar o modal
                const modal = document.getElementById('modalstock' + idProduto);
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                // Recarregar a tabela
                tabela.ajax.reload();
            });
        } else {
            Swal.fire({
                title: "Erro!",
                icon: "error",
                html: data.msg,
                timer: 3000,
                timerProgressBar: true
            });
        }
    } catch (error) {
        Swal.fire({
            title: "Erro!",
            icon: "error",
            html: "Ocorreu um erro ao ajustar o estoque: " + error.message,
            timer: 3000,
            timerProgressBar: true
        });
    }
}

window.ajustarEstoque = ajustarEstoque;
