import sys, os

templates_path = ['.templates']
source_suffix = '.rst'
master_doc = 'index'
project = u'Cilex'
copyright = u'2013, Mike van Riel'

version = '1.0'
release = '1.0.0-alpha1'
pygments_style = 'sphinx'

html_theme = 'agogo'
html_static_path = ['.static']
htmlhelp_basename = 'Cilexdoc'

# -- Options for LaTeX output --------------------------------------------------

latex_elements = {
    'papersize': 'a4paper',
    'pointsize': '10pt',
}

# (source start file, target name, title, author, documentclass [howto/manual]).
latex_documents = [
  ('index', 'Cilex.tex', u'Cilex Documentation', u'Mike van Riel', 'manual'),
]

latex_logo = '.static/logo.png'

# -- Options for manual page output --------------------------------------------

# (source start file, name, description, authors, manual section).
man_pages = [
    ('index', 'cilex', u'Cilex Documentation', [u'Mike van Riel'], 1)
]

# -- Options for Texinfo output ------------------------------------------------

# (source start file, target name, title, author, dir menu entry, description,
# category)
texinfo_documents = [
  ('index', 'Cilex', u'Cilex Documentation', u'Mike van Riel', 'Cilex', 'One line description of project.',
   'Miscellaneous'),
]