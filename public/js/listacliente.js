import { Requests } from "./Requests.js";

const tabela = $('#tabela').DataTable({
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
        url: '/cliente/listcliente',
        type: 'POST'
    }
});

async function Delete(id) {
    document.getElementById('id').value = id;
    const response = await Requests.SetForm('form').Post('/cliente/delete');
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
        title: "Removido com sucesso!!",
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