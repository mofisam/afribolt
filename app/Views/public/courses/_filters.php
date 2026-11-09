<details open><summary>Category</summary><div class="pt-1">
  <?= $opt('category', '', 'All categories') ?>
  <?php foreach ($categories as $c): ?><?= $opt('category', $c['slug'], $c['name'] . ' (' . $c['n'] . ')') ?><?php endforeach; ?></div></details>
<details open><summary>Learn how</summary><div class="pt-1">
  <?= $opt('mode', '', 'Any') ?><?= $opt('mode', 'self_paced', 'Self-paced (on demand)') ?><?= $opt('mode', 'live', 'Live cohort') ?></div></details>
<details open><summary>Price</summary><div class="pt-1">
  <?= $opt('price', '', 'Any') ?><?= $opt('price', 'free', 'Free') ?><?= $opt('price', 'paid', 'Paid') ?></div></details>
<details><summary>Level</summary><div class="pt-1">
  <?= $opt('level', '', 'All levels') ?><?= $opt('level', 'beginner', 'Beginner') ?><?= $opt('level', 'intermediate', 'Intermediate') ?><?= $opt('level', 'advanced', 'Advanced') ?></div></details>
<details><summary>Rating</summary><div class="pt-1">
  <?= $opt('rating', '', 'Any') ?><?= $opt('rating', '4.5', '4.5 & up') ?><?= $opt('rating', '4', '4.0 & up') ?><?= $opt('rating', '3', '3.0 & up') ?></div></details>
