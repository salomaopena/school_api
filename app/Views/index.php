<!-- Botão só aparece para quem tem permissão -->
<?php if (hasPermission('notas:create')): ?>
    <button class="btn btn-primary">Lançar Nota</button>
<?php endif; ?>

<!-- Menu contextual por role -->
<?php if (hasRole(['professor', 'director'])): ?>
    <li><a href="<?= site_url('professor/turmas') ?>">Gerir Turmas</a></li>
<?php endif; ?>

<?php if (hasPermission('users:delete')): ?>
    <button onclick="excluirUsuario(<?= $user->id ?>)">Excluir</button>
<?php endif; ?>



<h1>Dashboard</h1>
<p>Bem-vindo, <?= auth()->user()['nome_completo'] ?>!</p>

<?php if (has_permission('usuarios.ler')): ?>
    <a href="<?= site_url('admin/users') ?>" class="btn btn-primary">Gerenciar Usuários</a>
<?php endif; ?>

<div class="roles">
    <strong>Seus papéis:</strong> <?= implode(', ', auth()->user()['roles']) ?>
</div>