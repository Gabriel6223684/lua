// Função para editar registro
function Editar(id) {
    window.location.href = '/pagamento/alterar/' + id;
}

// Função para excluir registro
async function Delete(id) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: "Você não poderá reverter isso!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('/pagamento/delete', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Excluído!',
                    text: data.msg,
                    timer: 2000,
                    timerProgressBar: true
                });
                // Recarrega a página após exclusão
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.msg
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao excluir o registro.'
            });
        }
    }
}

// Expõe as funções para o escopo global
window.Editar = Editar;
window.Delete = Delete;

const conf = {
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
        url: 'https://cdn.datatables.net/plug-ins/2.3.7/i18n/pt-BR.json',
        searchPlaceholder: 'Digite sua pesquisa...'
    },
    ajax: {
        url: '/pagamento/listapaymentterms',
        type: 'POST'
    },
    layout: {
        topStart: 'search',
        topEnd: 'pageLength',
        bottomStart: 'info',
        bottomEnd: 'paging'
    },
    // ✅ Aqui aplicamos a estilização após a tabela estar pronta
    initComplete: function () {
        setTimeout(() => {
            // Remove o label "Pesquisar"
            const label = document.querySelector('.dt-search label');
            if (label) {
                label.remove(); // Remove completamente do DOM
            }
            // Seleciona div que contém o campo de pesquisa
            const searchDiv = document.querySelector('.row > div.dt-layout-start');
            if (searchDiv) {
                searchDiv.classList.remove('col-md-auto');
                searchDiv.classList.add('col-lg-6', 'col-md-6', 'col-sm-12');
            }
            const divSearch = document.querySelector('.dt-search');
            if (divSearch) {
                divSearch.classList.add('w-100'); // ou w-100, w-75 etc.
            }

            const input = document.querySelector('#dt-search-0');
            if (input) {
                input.classList.remove('form-control-sm'); // ou w-100, w-75 etc.
                input.classList.add('form-control-md', 'w-100'); // ou w-100, w-75 etc.
                // Remove margem e padding da esquerda
                input.style.marginLeft = '0';
                input.focus();
            }
            const pageLength = document.querySelector('#dt-length-0');
            if (pageLength) {
                pageLength.classList.add('form-select-md'); // ou form-select-sm, dependendo do tamanho desejado
            }
        }, 100);
    }
};

const table = new $("#tabela").DataTable(conf);
